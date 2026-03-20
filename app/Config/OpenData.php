<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Defines public municipal open-data sources that can be synced into the app.
 */
class OpenData extends BaseConfig
{
    /**
     * Available Socrata-backed sources keyed for UI forms and CLI commands.
     *
     * @var array<string, array<string, int|string>>
     */
    public array $sources = [
        'edmonton-benches' => [
            'label' => 'City of Edmonton parks seating',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'shmq-hc3f',
            'endpoint' => 'https://data.edmonton.ca/resource/shmq-hc3f.json',
            'department_code' => 'PARKS',
            'category_name' => 'Park Bench',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-hydrants' => [
            'label' => 'City of Edmonton fire hydrants',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'x4n2-2ke2',
            'endpoint' => 'https://data.edmonton.ca/resource/x4n2-2ke2.json',
            'department_code' => 'ROADS',
            'category_name' => 'Fire Hydrant',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-trees' => [
            'label' => 'City of Edmonton city-owned trees',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'eecg-fc54',
            'endpoint' => 'https://data.edmonton.ca/resource/eecg-fc54.json',
            'department_code' => 'PARKS',
            'category_name' => 'City Tree',
            'default_limit' => 250,
            'default_batch_size' => 2000,
        ],
        'edmonton-playgrounds' => [
            'label' => 'City of Edmonton playgrounds',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => '9nqb-w48x',
            'endpoint' => 'https://data.edmonton.ca/resource/9nqb-w48x.json',
            'department_code' => 'PARKS',
            'category_name' => 'Playground Structure',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-spray-parks' => [
            'label' => 'City of Edmonton spray parks',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'jyra-si4k',
            'endpoint' => 'https://data.edmonton.ca/resource/jyra-si4k.json',
            'department_code' => 'PARKS',
            'category_name' => 'Spray Park',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-parks' => [
            'label' => 'City of Edmonton parks',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'gdd9-eqv9',
            'endpoint' => 'https://data.edmonton.ca/resource/gdd9-eqv9.json',
            'department_code' => 'PARKS',
            'category_name' => 'Park',
            'default_limit' => 100,
            'default_batch_size' => 500,
        ],
        'edmonton-streetlights' => [
            'label' => 'City of Edmonton streetlights',
            'system' => 'City of Edmonton Open Data',
            'dataset_id' => 'rxke-mcvd',
            'endpoint' => 'https://data.edmonton.ca/resource/rxke-mcvd.json',
            'department_code' => 'ROADS',
            'category_name' => 'Streetlight',
            'default_limit' => 250,
            'default_batch_size' => 2000,
        ],
        'edmonton-drainage-manholes' => [
            'label' => 'Edmonton drainage manholes',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => '6waz-yxqq',
            'endpoint' => 'https://data.edmonton.ca/resource/6waz-yxqq.json',
            'department_code' => 'ROADS',
            'category_name' => 'Drainage Manhole',
            'default_limit' => 250,
            'default_batch_size' => 2000,
        ],
        'edmonton-drainage-catch-basins' => [
            'label' => 'Edmonton drainage catch basins',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => '5xxs-hqn7',
            'endpoint' => 'https://data.edmonton.ca/resource/5xxs-hqn7.json',
            'department_code' => 'ROADS',
            'category_name' => 'Catch Basin',
            'default_limit' => 250,
            'default_batch_size' => 2000,
        ],
        'edmonton-drainage-pump-stations' => [
            'label' => 'Edmonton drainage pump stations',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => 'yhez-gf32',
            'endpoint' => 'https://data.edmonton.ca/resource/yhez-gf32.json',
            'department_code' => 'ROADS',
            'category_name' => 'Pump Station',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-drainage-outfalls' => [
            'label' => 'Edmonton drainage outfalls',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => 'gpxt-s37s',
            'endpoint' => 'https://data.edmonton.ca/resource/gpxt-s37s.json',
            'department_code' => 'ROADS',
            'category_name' => 'Drainage Outfall',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-drainage-inlets-outlets' => [
            'label' => 'Edmonton drainage inlets and outlets',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => 'qpsa-ivnn',
            'endpoint' => 'https://data.edmonton.ca/resource/qpsa-ivnn.json',
            'department_code' => 'ROADS',
            'category_name' => 'Drainage Inlet/Outlet',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-drainage-pipe-segments' => [
            'label' => 'Edmonton drainage pipe segments',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => 'bh8y-pn5j',
            'endpoint' => 'https://data.edmonton.ca/resource/bh8y-pn5j.json',
            'department_code' => 'ROADS',
            'category_name' => 'Drainage Pipe Segment',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-drainage-catch-basin-leads' => [
            'label' => 'Edmonton drainage catch basin leads',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => 'jufi-ds3w',
            'endpoint' => 'https://data.edmonton.ca/resource/jufi-ds3w.json',
            'department_code' => 'ROADS',
            'category_name' => 'Catch Basin Lead',
            'default_limit' => 100,
            'default_batch_size' => 1000,
        ],
        'edmonton-stormwater-facilities' => [
            'label' => 'Edmonton stormwater management facilities',
            'system' => 'EPCOR via City of Edmonton Open Data',
            'dataset_id' => '72ee-mmkx',
            'endpoint' => 'https://data.edmonton.ca/resource/72ee-mmkx.json',
            'department_code' => 'ROADS',
            'category_name' => 'Stormwater Facility',
            'default_limit' => 100,
            'default_batch_size' => 500,
        ],
    ];
}
