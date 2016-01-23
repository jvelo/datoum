<?php

namespace Jvelo\Datoum;

use Illuminate\Database\Eloquent\Model;
use \Eloquent\Dialect\Json;

class Data extends Model {

    use UuidKey;
    use Json;

    protected $table = 'documents';

    protected $jsonColumns = ['data'];

    public function __construct()
    {
        parent::__construct();
        $this->data = '{}';
    }

}