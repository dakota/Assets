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
        $table->addColumn('title', 'string', [
            'null' => false,
            'default' => null,
        ]);
        $table->addColumn('slug', 'string', [
            'null' => false,
            'default' => null,
        ]);
        $table->addColumn('body', 'text', [
            'null' => false,
            'default' => null,
        ]);
        $table->addColumn('excerpt',
            'text', ['null' => true, 'default' => null]);
        $table->addColumn('status', 'boolean', ['null' => false, 'default' => '0']);
        $table->addColumn('sticky', 'boolean', ['null' => false, 'default' => '0']);
        $table->addColumn('visibility_roles',
            'text', ['null' => true, 'default' => null]);
        $table->addColumn('hash', 'string', ['null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('plugin', 'string', ['null' => true, 'default' => null]);
        $table->addColumn('import_path', 'string', ['length' => 512]);
        $table->addColumn('asset_count', 'integer', ['null' => true]);
        $table->addColumn('created', 'datetime', ['null' => true]);
        $table->addColumn('created_by', 'integer', ['null' => true]);
        $table->addColumn('updated', 'datetime', ['null' => true]);
        $table->addColumn('updated_by', 'integer', ['null' => true]);
        $table->addIndex('hash');
        $table->create();

        $table = $this->table('assets');
        $table->addColumn('parent_asset_id', 'integer', ['null' => true, 'default' => null]);
        $table->addColumn('foreign_key', 'string', ['null' => true, 'default' => null, 'length' => 36]);
        $table->addColumn('model', 'string', ['null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('filename', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('filesize', 'integer', ['null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('width', 'integer', ['null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('height', 'integer', ['null' => true, 'default' => null, 'length' => 16]);
        $table->addColumn('mime_type', 'string', ['null' => false, 'default' => null, 'length' => 32]);
        $table->addColumn('extension', 'string', ['null' => true, 'default' => null, 'length' => 5]);
        $table->addColumn('hash', 'string', ['null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('path', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('adapter', 'string', [
            'null' => true,
            'default' => null,
            'length' => 32,
            'comment' => 'Gaufrette Storage Adapter Class'
        ]);
        $table->addColumn('created', 'datetime', ['null' => true, 'default' => null]);
        $table->addColumn('modified', 'datetime', ['null' => true, 'default' => null]);
        $table->addIndex(['hash', 'path']);
        $table->addIndex(['model', 'foreign_key']);
        $table->addIndex(['parent_asset_id', 'width', 'height'], ['unique' => true]);
        $table->create();

        $table = $this->table('asset_usages');
        $table->addColumn('asset_id', 'integer', ['null' => false, 'default' => null]);
        $table->addColumn('model', 'string', ['null' => true, 'default' => null, 'length' => 64]);
        $table->addColumn('foreign_key', 'string', ['null' => true, 'default' => null, 'length' => 36]);
        $table->addColumn('type', 'string', ['length' => 20, 'null' => true, 'default' => null]);
        $table->addColumn('url', 'string', ['length' => 512, 'null' => true]);
        $table->addColumn('created', 'datetime', ['null' => true, 'default' => null]);
        $table->addColumn('modified', 'datetime', ['null' => true, 'default' => null]);
        $table->addColumn('params', 'text', ['null' => true, 'default' => null]);
        $table->addIndex(['model', 'foreign_key']);
        $table->create();
    }
}
