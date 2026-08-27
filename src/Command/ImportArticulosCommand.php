<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-articulos',
    description: 'Importa artículos de e-commerce desde un archivo CSV (solo para entorno de desarrollo)',
)]
class ImportArticulosCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('csv', InputArgument::OPTIONAL, 'Ruta al CSV', '%kernel.project_dir%/articulos.csv');
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Actualizar registros existentes (INSERT ... ON DUPLICATE KEY UPDATE)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvPath = $input->getArgument('csv');

        if (str_starts_with($csvPath, '%kernel.project_dir%')) {
            $csvPath = str_replace('%kernel.project_dir%', dirname(__DIR__, 2), $csvPath);
        }

        if (!file_exists($csvPath)) {
            $io->error("Archivo no encontrado: $csvPath");
            return Command::FAILURE;
        }

        $doUpdate = $input->getOption('update');
        $handle = fopen($csvPath, 'r');
        $headers = null;
        $created = $skipped = 0;
        $batchSize = 100;
        $batch = [];

        $io->info("Importando desde: $csvPath");

        // Obtener códigos existentes para skip check
        $existentes = array_flip($this->connection->fetchFirstColumn('SELECT Codigo_Calipso FROM articulos_ecommerce'));

        while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            $data = array_combine($headers, $row);
            $codigo = trim($data['Codigo_Calipso'] ?? '');

            if (empty($codigo)) {
                continue;
            }

            if (isset($existentes[$codigo]) && !$doUpdate) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'Codigo_Calipso'                   => $codigo,
                'Esquema'                           => trim($data['Esquema'] ?? '') ?: null,
                'Articulo_IdeaConnector'            => trim($data['Articulo_IdeaConnector'] ?? '') ?: null,
                'Codigo_IdeaConnector'              => trim($data['Codigo_IdeaConnector'] ?? '') ?: null,
                'Codigo_Rockwell'                   => trim($data['Codigo_Rockwell'] ?? '') ?: null,
                'Descripcion'                       => trim($data['Descripcion'] ?? '') ?: null,
                'Descripcion_Ideaconector'          => trim($data['Descripcion_Ideaconector'] ?? '') ?: null,
                'Descripcion_Tecnica_Ideaconector'  => trim($data['Descripcion_Tecnica_Ideaconector'] ?? '') ?: null,
                'Imagen'                            => trim($data['Imagen'] ?? '') ?: null,
                'Soluciones'                        => trim($data['Soluciones'] ?? '') ?: null,
                'Categoria_Advisor'                 => trim($data['Categoria_Advisor'] ?? '') ?: null,
                'SubCategoria_Advisor'              => trim($data['SubCategoria_Advisor'] ?? '') ?: null,
                'ID_BU'                             => trim($data['ID_BU'] ?? '') ?: null,
                'BU'                                => trim($data['BU'] ?? '') ?: null,
                'Id_Proveedor'                      => trim($data['Id_Proveedor'] ?? '') ?: null,
                'Proveedor'                         => trim($data['Proveedor'] ?? '') ?: null,
                'Marca'                             => trim($data['Marca'] ?? '') ?: null,
            ];
            $created++;

            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch, $doUpdate);
                $batch = [];
                $io->write('.');
            }
        }

        fclose($handle);

        if (!empty($batch)) {
            $this->insertBatch($batch, $doUpdate);
        }

        $io->newLine();
        $io->success("Importación completada: $created insertados/actualizados, $skipped omitidos.");
        return Command::SUCCESS;
    }

    private function insertBatch(array $rows, bool $update): void
    {
        if (empty($rows)) return;

        $cols = array_keys($rows[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $sql = 'INSERT INTO articulos_ecommerce (`' . implode('`, `', $cols) . '`) VALUES ' . $allPlaceholders;

        if ($update) {
            $updateCols = array_filter($cols, fn($c) => $c !== 'Codigo_Calipso');
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $updateCols));
        } else {
            $sql .= ' ON DUPLICATE KEY UPDATE Codigo_Calipso = Codigo_Calipso'; // no-op skip
        }

        $values = [];
        foreach ($rows as $row) {
            foreach ($row as $v) {
                $values[] = $v;
            }
        }

        $this->connection->executeStatement($sql, $values);
    }
}
