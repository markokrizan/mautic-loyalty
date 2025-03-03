<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\DoctrineStepType;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineStep implements StepInterface
{
    /**
     * Database driver.
     */
    public $driver = 'pdo_mysql';

    /**
     * Database host.
     */
    public $host = 'localhost';

    /**
     * Database table prefix.
     * Required in step.
     *
     * @var string
     */
    public $table_prefix;

    /**
     * Database connection port.
     */
    public $port = 3306;

    /**
     * Database name.
     */
    public $name;

    /**
     * Database user.
     */
    public $user;

    /**
     * Database user's password.
     *
     * @var string
     */
    public $password;

    /**
     * Backup tables if they exist; otherwise drop them.
     * Required in step.
     *
     * @var bool
     */
    public $backup_tables = true;

    /**
     * Prefix for backup tables.
     * Required in step.
     *
     * @var string
     */
    public $backup_prefix = 'bak_';

    public function __construct(Configurator $configurator)
    {
        $parameters = $configurator->getParameters();

        foreach ($parameters as $key => $value) {
            if (str_starts_with($key, 'db_')) {
                $parameters[substr($key, 3)] = $value;
                $key                         = substr($key, 3);
                $this->$key                  = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType(): string
    {
        return DoctrineStepType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        $messages = [];

        if (!class_exists('\PDO')) {
            $messages[] = 'mautic.install.pdo.mandatory';
        } else {
            $drivers = \PDO::getAvailableDrivers();
            if (0 == count($drivers)) {
                $messages[] = 'mautic.install.pdo.drivers';
            }
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     */
    public function update(StepInterface $data): array
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            $parameters['db_'.$key] = $value;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return '@MauticInstall/Install/doctrine.html.twig';
    }

    /**
     * Return the key values of the available driver array.
     * Required in step.
     *
     * @see \Mautic\InstallBundle\Configurator\Form\DoctrineStepType::buildForm()
     */
    public static function getDriverKeys(): array
    {
        return array_keys(static::getDrivers());
    }

    /**
     * Fetches the available database drivers for the environment.
     *
     * @return array
     */
    public static function getDrivers()
    {
        $mauticSupported = [
            'pdo_mysql' => 'MySQL PDO (Recommended)',
            'mysqli'    => 'MySQLi',
        ];

        $supported = [];

        // Add PDO drivers if supported
        if (class_exists('\PDO')) {
            $pdoDrivers = \PDO::getAvailableDrivers();

            foreach ($pdoDrivers as $driver) {
                if (array_key_exists('pdo_'.$driver, $mauticSupported)) {
                    $supported['pdo_'.$driver] = $mauticSupported['pdo_'.$driver];
                }
            }
        }

        // Add MySQLi if available
        if (function_exists('mysqli_connect')) {
            $supported['mysqli'] = $mauticSupported['mysqli'];
        }

        return $supported;
    }
}
