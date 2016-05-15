<?php
use Migrations\AbstractMigration;

class InitialAssetMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('attachments');
        $table->addColumn('title', [
            'type' => 'string',
            'null' => false,
            'default' => null,
            'collate' => 'utf8_unicode_ci',
            'charset' => 'utf8'
        ]);
        $table->addColumn('slug', [
            'type' => 'string',
            'null' => false,
            'default' => null,
            'collate' => 'utf8_unicode_ci',
            'charset' => 'utf8'
        ]);
        $table->addColumn('body', [
            'type' => 'text',
            'null' => false,
            'default' => null,
            'collate' => 'utf8_unicode_ci',
            'charset' => 'utf8'
        ]);
        $table->addColumn('excerpt',
            ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8']);
        $table->addColumn('status', ['type' => 'boolean', 'null' => false, 'default' => '0']);
        $table->addColumn('sticky', ['type' => 'boolean', 'null' => false, 'default' => '0']);
        $table->addColumn('visibility_roles',
            ['type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_unicode_ci', 'charset' => 'utf8']);
        $table->addColumn('hash', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('plugin', ['type' => 'string', 'null' => true, 'default' => null]);
        $table->addColumn('import_path', ['type' => 'string', 'length' => 512]);
        $table->addColumn('asset_count', ['type' => 'integer', 'null' => true]);
        $table->addColumn('created', ['type' => 'datetime', 'null' => true]);
        $table->addColumn('created_by', ['type' => 'integer', 'null' => true]);
        $table->addColumn('updated', ['type' => 'datetime', 'null' => true]);
        $table->addColumn('updated_by', ['type' => 'integer', 'null' => true]);
        $table->create();

        $table = $this->table('assets');
        $table->addColumn('parent_asset_id', ['type' => 'integer', 'null' => true, 'default' => null]);
        $table->addColumn('foreign_key', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36]);
        $table->addColumn('model', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('filename', ['type' => 'string', 'null' => false, 'default' => null]);
        $table->addColumn('filesize', ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('width', ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('height', ['type' => 'integer', 'null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('mime_type', ['type' => 'string', 'null' => false, 'default' => null, 'length' => 32]);
        $table->addColumn('extension', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 5]);
        $table->addColumn('hash', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('path', ['type' => 'string', 'null' => false, 'default' => null]);
        $table->addColumn('adapter', [
            'type' => 'string',
            'null' => true,
            'default' => null,
            'length' => 32,
            'comment' => 'Gaufrette Storage Adapter Class'
        ]);
        $table->addColumn('created', ['type' => 'datetime', 'null' => true, 'default' => null]);
        $table->addColumn('modified', ['type' => 'datetime', 'null' => true, 'default' => null]);
        $table->create();

        $table = $this->table('asset_usages');
        $table->addColumn('asset_id', ['type' => 'integer', 'null' => false, 'default' => null]);
        $table->addColumn('model', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('foreign_key', ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36]);
        $table->addColumn('type', ['type' => 'string', 'length' => 20, 'null' => true, 'default' => null]);
        $table->addColumn('url', ['type' => 'string', 'length' => 512, 'null' => true]);
        $table->addColumn('created', ['type' => 'datetime', 'null' => true, 'default' => null]);
        $table->addColumn('modified', ['type' => 'datetime', 'null' => true, 'default' => null]);
        $table->addColumn('params', ['type' => 'text', 'null' => true, 'default' => null]);
        $table->create();
    }
}
