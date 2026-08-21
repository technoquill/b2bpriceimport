<?php

declare(strict_types=1);

namespace B2B\PriceImport\Command;

use B2B\PriceImport\Config\B2BPriceImportConfig;
use B2B\PriceImport\DTO\ImportRunOptions;
use B2B\PriceImport\Repository\B2BPriceImportConfigRepository;
use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Service\ImportFileScannerService;
use B2B\PriceImport\Service\ImportLockService;
use B2B\PriceImport\Service\AuditLogService;
use B2B\PriceImport\Service\PriceImportRunService;
use B2B\PriceImport\Service\PriceImportParser;
use B2B\PriceImport\Service\PriceImportProcessor;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class RunPriceImportCommand extends Command
{
    protected static $defaultName = 'b2b:price-import:run';

    private const EXIT_SUCCESS = 0;
    private const EXIT_FAILURE = 1;

    private const TYPE_PARSE = 'parse';
    private const TYPE_PROCESS = 'process';
    private const TYPE_ALL = 'all';

    private const FORMAT_TEXT = 'text';
    private const FORMAT_JSON = 'json';

    public function __construct(
        private readonly ?ImportRepository $repository = null,
        private readonly ?PriceImportParser $parser = null,
        private readonly ?PriceImportProcessor $processor = null,
        private readonly ?ImportLockService $lockService = null,
        private readonly ?ImportFileScannerService $scanner = null,
        private readonly ?B2BPriceImportConfigRepository $configRepository = null,
        private readonly ?PriceImportRunService $runner = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run B2B price import from CLI.')
            ->addOption('import-id', null, InputOption::VALUE_REQUIRED, 'Import ID to run. If omitted, the command scans the filesystem inbox first.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Optional CSV filename from the import scan directory, including extension.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Import stage: parse, process or all. Overrides module configuration.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum rows to process per processor batch. Overrides module configuration.')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Maximum command runtime in seconds. Overrides module configuration.')
            ->addOption('lock-ttl', null, InputOption::VALUE_REQUIRED, 'Import lock TTL in seconds. Overrides module configuration.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force lock replacement.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json. Overrides module configuration.')
            ->addOption('scan-dir', null, InputOption::VALUE_REQUIRED, 'Directory to scan for fresh CSV files. Overrides module configuration.')
            ->addOption('max-file-age-hours', null, InputOption::VALUE_REQUIRED, 'Only register CSV files not older than this value. Overrides module configuration.')
            ->addOption('scan-limit', null, InputOption::VALUE_REQUIRED, 'Maximum new filesystem imports to register per command run. Overrides module configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $summary = [
            'success' => false,
            'error_code' => null,
            'import_id' => null,
            'type' => null,
            'file' => null,
            'scan' => null,
            'parse' => null,
            'process' => [
                'processed' => 0,
                'failed' => 0,
                'batches' => 0,
            ],
            'message' => null,
        ];

        try {
            $type = $this->resolveType($input);
            $limit = $this->resolvePositiveInt(
                $input,
                'limit',
                $this->getConfigRepository()->getImportBatchLimit(),
                1,
                5000
            );
            $timeLimit = $this->resolvePositiveInt(
                $input,
                'time-limit',
                $this->getConfigRepository()->getImportTimeLimit(),
                1,
                3600
            );
            $lockTtl = $this->resolvePositiveInt(
                $input,
                'lock-ttl',
                $this->getConfigRepository()->getImportLockTtl(),
                1,
                3600
            );
            $format = $this->resolveFormat($input);
            $force = (bool) $input->getOption('force');
            $scanDirectory = $this->resolveString(
                $input,
                'scan-dir',
                $this->getConfigRepository()->getImportScanDir()
            );
            $maxFileAgeHours = $this->resolvePositiveInt(
                $input,
                'max-file-age-hours',
                $this->getConfigRepository()->getImportMaxFileAgeHours(),
                1,
                168
            );
            $scanLimit = $this->resolvePositiveInt(
                $input,
                'scan-limit',
                $this->getConfigRepository()->getImportScanLimit(),
                1,
                50
            );
            $rawImportId = (int) $input->getOption('import-id');
            $requestedFilename = $this->resolveString($input, 'file', '');
            $runner = $this->runner ?: new PriceImportRunService(
                $this->repository,
                $this->parser,
                $this->processor,
                $this->lockService,
                $this->scanner
            );

            $summary = $runner->run(new ImportRunOptions(
                importId: $rawImportId > 0 ? $rawImportId : null,
                type: $type,
                batchLimit: $limit,
                timeLimit: $timeLimit,
                lockTtl: $lockTtl,
                forceLock: $force,
                scanDirectory: $scanDirectory,
                maxFileAgeHours: $maxFileAgeHours,
                scanLimit: $scanLimit,
                filename: $requestedFilename !== '' ? $requestedFilename : null
            ));

            $this->writeSummary($output, $summary, $format);

            return $summary['success'] ? self::EXIT_SUCCESS : self::EXIT_FAILURE;
        } catch (Throwable $exception) {
            $summary['error_code'] = PriceImportRunService::ERROR_FAILED;
            $summary['message'] = $exception->getMessage();

            (new AuditLogService())->record(
                'system.cli_command_failed',
                'system',
                'error',
                $exception->getMessage(),
                'b2b:price-import:run',
                null,
                null,
                ['error_code' => PriceImportRunService::ERROR_FAILED],
                'cli'
            );

            $format = self::FORMAT_TEXT;
            try {
                $format = $this->resolveFormat($input);
            } catch (Throwable) {
            }

            $this->writeSummary($output, $summary, $format);

            return self::EXIT_FAILURE;
        }
    }

    private function resolveType(InputInterface $input): string
    {
        $type = $this->resolveString($input, 'type', $this->getConfigRepository()->getImportRunType());
        $allowedTypes = [self::TYPE_PARSE, self::TYPE_PROCESS, self::TYPE_ALL];

        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid --type. Allowed values: parse, process, all.');
        }

        return $type;
    }

    private function resolveFormat(InputInterface $input): string
    {
        $format = $this->resolveString($input, 'format', $this->getConfigRepository()->getImportOutputFormat());
        $allowedFormats = [self::FORMAT_TEXT, self::FORMAT_JSON];

        if (!in_array($format, $allowedFormats, true)) {
            throw new InvalidArgumentException('Invalid --format. Allowed values: text, json.');
        }

        return $format;
    }

    private function resolvePositiveInt(InputInterface $input, string $optionName, int $default, int $min, int $max): int
    {
        $value = $input->getOption($optionName);

        if ($value === null || $value === '') {
            $value = $default;
        }

        $value = (int) $value;

        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException(sprintf('Option --%s must be between %d and %d.', $optionName, $min, $max));
        }

        return $value;
    }

    private function resolveString(InputInterface $input, string $optionName, string $default): string
    {
        $value = $input->getOption($optionName);

        if ($value === null || $value === '') {
            return $default;
        }

        return trim((string) $value);
    }

    private function getConfigRepository(): B2BPriceImportConfigRepository
    {
        return $this->configRepository ?: new B2BPriceImportConfigRepository(new B2BPriceImportConfig());
    }

    private function writeSummary(OutputInterface $output, array $summary, string $format): void
    {
        if ($format === self::FORMAT_JSON) {
            $output->writeln((string) json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return;
        }

        $output->writeln('B2B price import');
        $output->writeln('Status: ' . ($summary['success'] ? 'success' : 'failed'));
        $output->writeln('Import ID: ' . ($summary['import_id'] ?? '-'));
        $output->writeln('Type: ' . ($summary['type'] ?? '-'));
        $output->writeln(
            'File: ' . ($summary['file'] ?? ($summary['success'] ? 'automatic scan' : '-'))
        );

        if (is_array($summary['scan'])) {
            $output->writeln('Scan created: ' . count($summary['scan']['created'] ?? []));
            $output->writeln('Scan skipped: ' . count($summary['scan']['skipped'] ?? []));

            $skippedReasons = [];
            foreach (($summary['scan']['skipped'] ?? []) as $skippedFile) {
                $reason = (string) ($skippedFile['reason'] ?? 'unknown');
                $skippedReasons[$reason] = ($skippedReasons[$reason] ?? 0) + 1;

                if ($output->isVerbose()) {
                    $output->writeln(sprintf(
                        'Skipped file: %s (%s)',
                        (string) ($skippedFile['file'] ?? '-'),
                        $reason
                    ));
                }
            }

            if ($skippedReasons !== []) {
                $reasonSummary = [];
                foreach ($skippedReasons as $reason => $count) {
                    $reasonSummary[] = sprintf('%s=%d', $reason, $count);
                }

                $output->writeln('Scan skipped reasons: ' . implode(', ', $reasonSummary));
            }
        }

        if (is_array($summary['parse'])) {
            $output->writeln('Parse parsed: ' . (int) ($summary['parse']['parsed'] ?? 0));
            $output->writeln('Parse valid: ' . (int) ($summary['parse']['valid'] ?? 0));
            $output->writeln('Parse failed: ' . (int) ($summary['parse']['failed'] ?? 0));
        }

        $output->writeln('Process batches: ' . (int) $summary['process']['batches']);
        $output->writeln('Process processed: ' . (int) $summary['process']['processed']);
        $output->writeln('Process failed: ' . (int) $summary['process']['failed']);
        $output->writeln('Message: ' . ($summary['message'] ?? '-'));
    }
}
