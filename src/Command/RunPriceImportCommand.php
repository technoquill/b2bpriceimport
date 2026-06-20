<?php

declare(strict_types=1);

namespace B2B\PriceImport\Command;

use B2B\PriceImport\Repository\ImportRepository;
use B2B\PriceImport\Service\ImportFileScannerService;
use B2B\PriceImport\Service\ImportLockService;
use B2B\PriceImport\Service\PriceImportParser;
use B2B\PriceImport\Service\PriceImportProcessor;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class RunPriceImportCommand extends Command
{
    protected static $defaultName = 'b2b:price-import:run';

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
        private readonly ?ImportFileScannerService $scanner = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run B2B price import from CLI.')
            ->addOption('import-id', null, InputOption::VALUE_REQUIRED, 'Import ID to run. If omitted, the command scans the filesystem inbox first.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Import stage: parse, process or all.', self::TYPE_ALL)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum rows to process per processor batch.', '500')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Maximum command runtime in seconds.', '55')
            ->addOption('lock-ttl', null, InputOption::VALUE_REQUIRED, 'Import lock TTL in seconds.', '120')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force lock replacement.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json.', self::FORMAT_TEXT)
            ->addOption('scan-dir', null, InputOption::VALUE_REQUIRED, 'Directory to scan for fresh CSV files.', $this->getDefaultScanDirectory())
            ->addOption('max-file-age-hours', null, InputOption::VALUE_REQUIRED, 'Only register CSV files not older than this value.', '24')
            ->addOption('scan-limit', null, InputOption::VALUE_REQUIRED, 'Maximum new filesystem imports to register per command run.', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = time();
        $summary = [
            'success' => false,
            'import_id' => null,
            'type' => null,
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
            $repository = $this->repository ?: new ImportRepository();
            $type = $this->resolveType($input);
            $limit = $this->resolvePositiveInt($input, 'limit', 500, 1, 5000);
            $timeLimit = $this->resolvePositiveInt($input, 'time-limit', 55, 1, 3600);
            $lockTtl = $this->resolvePositiveInt($input, 'lock-ttl', 120, 1, 3600);
            $format = $this->resolveFormat($input);
            $force = (bool) $input->getOption('force');

            $idImport = $this->resolveImportIdOrScan($input, $repository, $summary);
            if ($idImport === null) {
                $summary['success'] = true;
                $summary['message'] = 'No eligible CSV file found for import.';
                $this->writeSummary($output, $summary, $format);

                return Command::SUCCESS;
            }

            $summary['import_id'] = $idImport;
            $summary['type'] = $type;

            $lockName = 'b2b_price_import_' . $idImport;
            $lockService = $this->lockService ?: new ImportLockService();

            if (!$lockService->acquire($lockName, $lockTtl, $force)) {
                throw new RuntimeException('Import is locked by another process.');
            }

            try {
                if ($type === self::TYPE_PARSE || $type === self::TYPE_ALL) {
                    $summary['parse'] = ($this->parser ?: new PriceImportParser())->parse($idImport);
                }

                if ($type === self::TYPE_PROCESS || $type === self::TYPE_ALL) {
                    do {
                        $result = ($this->processor ?: new PriceImportProcessor())->process($idImport, $limit);
                        $summary['process']['processed'] += (int) ($result['processed'] ?? 0);
                        $summary['process']['failed'] += (int) ($result['failed'] ?? 0);
                        $summary['process']['batches']++;

                        $hasMoreWork = ((int) ($result['processed'] ?? 0) + (int) ($result['failed'] ?? 0)) >= $limit;
                    } while ($hasMoreWork && (time() - $startedAt) < $timeLimit);
                }
            } finally {
                $lockService->release($lockName);
            }

            $summary['success'] = true;
            $summary['message'] = 'Import command finished.';

            $this->writeSummary($output, $summary, $format);

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $summary['message'] = $exception->getMessage();

            $format = self::FORMAT_TEXT;
            try {
                $format = $this->resolveFormat($input);
            } catch (Throwable) {
            }

            $this->writeSummary($output, $summary, $format);

            return Command::FAILURE;
        }
    }

    private function resolveImportIdOrScan(InputInterface $input, ImportRepository $repository, array &$summary): ?int
    {
        $idImport = (int) $input->getOption('import-id');

        if ($idImport > 0) {
            if ($repository->find($idImport) === null) {
                throw new RuntimeException('Import not found.');
            }

            return $idImport;
        }

        $scanDirectory = (string) $input->getOption('scan-dir');
        $maxFileAgeHours = $this->resolvePositiveInt($input, 'max-file-age-hours', 24, 1, 168);
        $scanLimit = $this->resolvePositiveInt($input, 'scan-limit', 1, 1, 50);

        $scan = ($this->scanner ?: new ImportFileScannerService($repository))->scanAndCreateImports(
            $scanDirectory,
            $maxFileAgeHours,
            $scanLimit
        );

        $summary['scan'] = $scan;

        if (empty($scan['created'][0]['id_import'])) {
            return null;
        }

        return (int) $scan['created'][0]['id_import'];
    }

    private function resolveType(InputInterface $input): string
    {
        $type = (string) $input->getOption('type');
        $allowedTypes = [self::TYPE_PARSE, self::TYPE_PROCESS, self::TYPE_ALL];

        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid --type. Allowed values: parse, process, all.');
        }

        return $type;
    }

    private function resolveFormat(InputInterface $input): string
    {
        $format = (string) $input->getOption('format');
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
            return $default;
        }

        $value = (int) $value;

        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException(sprintf('Option --%s must be between %d and %d.', $optionName, $min, $max));
        }

        return $value;
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

        if (is_array($summary['scan'])) {
            $output->writeln('Scan created: ' . count($summary['scan']['created'] ?? []));
            $output->writeln('Scan skipped: ' . count($summary['scan']['skipped'] ?? []));
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

    private function getDefaultScanDirectory(): string
    {
        return _PS_MODULE_DIR_ . 'b2bpriceimport/var/imports/inbox';
    }
}
