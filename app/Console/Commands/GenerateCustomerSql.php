<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateCustomerSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:generate-sql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate SQL insert statements for local_customers table from customers table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SQL generation for phpMyAdmin...');

        $total = Customer::count();
        
        if ($total === 0) {
            $this->warn('No customers found in the database.');
            return;
        }

        $this->info("Found {$total} customers. Generating bulk SQL statements...");

        $filename = 'customers_export.sql';
        $path = base_path($filename);

        // Standard SQL header for phpMyAdmin compatibility
        $header = "-- Generate SQL Export\n"
                . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n"
                . "SET AUTOCOMMIT = 0;\n"
                . "START TRANSACTION;\n"
                . "SET time_zone = \"+00:00\";\n\n"
                . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
                . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
                . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"
                . "/*!40101 SET NAMES utf8mb4 */;\n\n";

        File::put($path, $header);

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Use chunk for memory efficiency
        Customer::chunk(500, function ($customers) use ($path, $bar) {
            $sql = "INSERT INTO `local_customers` (`local_id`, `firstname`, `lastname`, `email`, `address`, `phone_number`) VALUES\n";
            
            $rows = [];
            foreach ($customers as $customer) {
                $local_id = (int)$customer->id;
                $firstname = $this->escape($customer->firstname);
                $lastname = $this->escape($customer->lastname);
                $email = $this->escape($customer->email);
                $address = $this->escape($customer->address);
                $phone_number = $this->escape($customer->phone_number);

                $rows[] = "({$local_id}, '{$firstname}', '{$lastname}', '{$email}', '{$address}', '{$phone_number}')";
                $bar->advance();
            }

            $sql .= implode(",\n", $rows) . ";\n\n";
            File::append($path, $sql);
        });

        $footer = "COMMIT;\n\n"
                . "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n"
                . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n"
                . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
        
        File::append($path, $footer);

        $bar->finish();
        $this->newLine();

        $this->info("SQL file optimized for phpMyAdmin generated successfully: " . $path);
    }

    /**
     * Escape string for SQL.
     * 
     * @param string|null $value
     * @return string
     */
    private function escape($value)
    {
        if (is_null($value)) {
            return '';
        }
        return str_replace("'", "''", $value);
    }
}
