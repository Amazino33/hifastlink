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
                $table->string('acctsessionid')->nullable();
                $table->string('realm')->nullable();
                $table->string('nasipaddress')->nullable();
                $table->string('nasportid')->nullable();
                $table->string('nasporttype')->nullable();
                $table->datetime('acctstarttime')->nullable();
                $table->datetime('acctupdatetime')->nullable();
                $table->datetime('acctstoptime')->nullable();
                $table->integer('acctsessiontime')->nullable();
                $table->string('acctauthentic')->nullable();
                $table->string('connectinfo_start')->nullable();
                $table->string('connectinfo_stop')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
                $table->string('calledstationid')->nullable();
                $table->string('callingstationid')->nullable();
                $table->string('acctterminatecause')->nullable();
                $table->string('servicetype')->nullable();
                $table->string('framedprotocol')->nullable();
                $table->string('framedipaddress')->nullable();
                $table->string('nasidentifier')->nullable();
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

        // Ensure NAS table exists on radius connection for router tests
        if (! Schema::connection('radius')->hasTable('nas')) {
            Schema::connection('radius')->create('nas', function (Blueprint $table) {
                $table->id();
                $table->string('nasname')->unique();
                $table->string('shortname')->nullable();
                $table->string('type')->nullable();
                $table->integer('ports')->nullable();
                $table->string('secret')->nullable();
                $table->string('server')->nullable();
                $table->string('community')->nullable();
                $table->string('description')->nullable();
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
        
        // Create radusergroup table (radius connection)
        if (! Schema::connection('radius')->hasTable('radusergroup')) {
            Schema::connection('radius')->create('radusergroup', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // --- Ensure same RADIUS tables exist on the default (sqlite) connection for tests ---
        if (! Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('radacct')) {
            Schema::create('radacct', function (Blueprint $table) {
                $table->id();
                $table->string('acctuniqueid')->unique()->nullable();
                $table->string('username');
                $table->string('acctsessionid')->nullable();
                $table->string('realm')->nullable();
                $table->string('nasipaddress')->nullable();
                $table->string('nasportid')->nullable();
                $table->string('nasporttype')->nullable();
                $table->datetime('acctstarttime')->nullable();
                $table->datetime('acctupdatetime')->nullable();
                $table->datetime('acctstoptime')->nullable();
                $table->integer('acctsessiontime')->nullable();
                $table->string('acctauthentic')->nullable();
                $table->string('connectinfo_start')->nullable();
                $table->string('connectinfo_stop')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
                $table->string('calledstationid')->nullable();
                $table->string('callingstationid')->nullable();
                $table->string('acctterminatecause')->nullable();
                $table->string('servicetype')->nullable();
                $table->string('framedprotocol')->nullable();
                $table->string('framedipaddress')->nullable();
                $table->string('nasidentifier')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('radgroupreply')) {
            Schema::create('radgroupreply', function (Blueprint $table) {
                $table->id();
                $table->string('groupname');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('radusergroup')) {
            Schema::create('radusergroup', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // Truncate radius tables on both connections so tests start with a clean slate
        \Illuminate\Support\Facades\DB::connection('radius')->table('radcheck')->truncate();
        \Illuminate\Support\Facades\DB::connection('radius')->table('radacct')->truncate();
        \Illuminate\Support\Facades\DB::connection('radius')->table('radgroupreply')->truncate();
        \Illuminate\Support\Facades\DB::connection('radius')->table('radreply')->truncate();
        \Illuminate\Support\Facades\DB::connection('radius')->table('radusergroup')->truncate();
        \Illuminate\Support\Facades\DB::connection('radius')->table('nas')->truncate();

        \Illuminate\Support\Facades\DB::table('radcheck')->truncate();
        \Illuminate\Support\Facades\DB::table('radacct')->truncate();
        \Illuminate\Support\Facades\DB::table('radgroupreply')->truncate();
        \Illuminate\Support\Facades\DB::table('radreply')->truncate();
        \Illuminate\Support\Facades\DB::table('radusergroup')->truncate();
    }
}


