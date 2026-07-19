<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class B2bPriceImport extends Module
{
    public function __construct()
    {
        $this->name = 'b2bpriceimport';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'B2B';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('B2B Price Import');
        $this->description = $this->l('B2B price import and discount matrix module.');

        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        return parent::install()
            && $this->installDatabase()
            && $this->installTabs();
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        return $this->uninstallTabs()
            && $this->uninstallDatabase()
            && parent::uninstall();
    }

    /**
     * @return void
     */
    public function getContent(): void
    {
        /*
         * Development safety:
         * If the module was already installed before installTabs() existed,
         * this will repair/create the admin tab without reinstalling the module.
         */
        $this->uninstallTab('AdminB2BDiscountMatrix');

        $this->installTabs();

        $link = $this->context->link->getAdminLink('AdminB2BPriceImport');

        Tools::redirectAdmin($link);
    }


    /**
     * @return bool
     */
    private function installDatabase(): bool
    {
        return $this->executeSqlFile(__DIR__ . '/sql/install.sql');
    }

    /**
     * @return bool
     */
    private function uninstallDatabase(): bool
    {
        return $this->executeSqlFile(__DIR__ . '/sql/uninstall.sql');
    }

    /**
     * @param $filePath
     * @return bool
     */
    private function executeSqlFile($filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $sql = file_get_contents($filePath);

        if ($sql === false || trim($sql) === '') {
            return false;
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        if (str_contains($sql, 'DB_CHARSET') || str_contains($sql, 'DB_COLLATION')) {
            $databaseEncoding = $this->getDatabaseEncoding();

            if ($databaseEncoding === null) {
                return false;
            }

            $sql = str_replace(
                ['DB_CHARSET', 'DB_COLLATION'],
                [$databaseEncoding['charset'], $databaseEncoding['collation']],
                $sql
            );
        }

        $queries = preg_split('/;\s*(?:\r\n|\r|\n)/', $sql);

        foreach ($queries as $query) {
            $query = trim($query);

            if ($query === '') {
                continue;
            }

            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{charset: string, collation: string}|null
     */
    private function getDatabaseEncoding(): ?array
    {
        $encoding = Db::getInstance()->getRow(
            'SELECT
                DEFAULT_CHARACTER_SET_NAME AS charset,
                DEFAULT_COLLATION_NAME AS collation
            FROM information_schema.SCHEMATA
            WHERE SCHEMA_NAME = DATABASE()'
        );

        if (!is_array($encoding)) {
            return null;
        }

        $charset = (string)($encoding['charset'] ?? '');
        $collation = (string)($encoding['collation'] ?? '');
        $validIdentifierPattern = '/^[a-zA-Z0-9_]+$/D';

        if (
            preg_match($validIdentifierPattern, $charset) !== 1
            || preg_match($validIdentifierPattern, $collation) !== 1
        ) {
            return null;
        }

        return [
            'charset' => $charset,
            'collation' => $collation,
        ];
    }

    private function installTabs(): bool
    {
        return $this->installTab(
            'AdminB2BPriceImport',
            'B2B Price Import',
            'AdminCatalog'
        );
    }

    /**
     * @param string $className
     * @param string $name
     * @param string $parentClassName
     * @return bool
     */
    private function installTab(string $className, string $name, string $parentClassName): bool
    {
        $existingTabId = (int)Tab::getIdFromClassName($className);

        if ($existingTabId > 0) {
            return true;
        }

        $idParent = (int)Tab::getIdFromClassName($parentClassName);

        if ($idParent <= 0) {
            $idParent = 0;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->module = $this->name;
        $tab->id_parent = $idParent;
        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int)$language['id_lang']] = $name;
        }

        return (bool)$tab->add();

    }

    /**
     * @return bool
     */
    private function uninstallTabs(): bool
    {
        return $this->uninstallTab('AdminB2BPriceImport')
            && $this->uninstallTab('AdminB2BDiscountMatrix');
    }

    /**
     * @param string $className
     * @return bool
     */
    private function uninstallTab(string $className): bool
    {
        $idTab = (int)Tab::getIdFromClassName($className);
        if ($idTab <= 0) {
            return true;
        }
        $tab = new Tab($idTab);

        return (bool)$tab->delete();
    }

}
