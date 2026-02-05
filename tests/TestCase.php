<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create RADIUS tables if they don't exist (for in-memory SQLite testing)
        $this->createRadiusTablesIfNeeded();
    }
    
    /**
     * Create necessary RADIUS tables for testing.
     */
    protected function createRadiusTablesIfNeeded(): void
    {
        // Create radcheck table
        if (! Schema::connection('radius')->hasTable('radcheck')) {
            Schema::connection('radius')->create('radcheck', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }
        
        // Create radacct table
        if (! Schema::connection('radius')->hasTable('radacct')) {
            Schema::connection('radius')->create('radacct', function (Blueprint $table) {
                $table->id();
                $table->string('acctuniqueid')->unique()->nullable();
                $table->string('username');
                $table->datetime('acctstarttime')->nullable();
                $table->datetime('acctupdatetime')->nullable();
                $table->datetime('acctstoptime')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
                $table->timestamps();
            });
        }
        
        // Create radgroupreply table
        if (! Schema::connection('radius')->hasTable('radgroupreply')) {
            Schema::connection('radius')->create('radgroupreply', function (Blueprint $table) {
                $table->id();
                $table->string('groupname');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }
        
        // Create radreply table
        if (! Schema::connection('radius')->hasTable('radreply')) {
            Schema::connection('radius')->create('radreply', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }
        
        // Create radusergroup table
        if (! Schema::connection('radius')->hasTable('radusergroup')) {
            Schema::connection('radius')->create('radusergroup', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }
    }
}

