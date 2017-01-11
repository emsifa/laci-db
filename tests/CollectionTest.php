<?php

use Emsifa\Laci\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{

    protected $filepath;
    protected $db;

    protected $dummyData = [
        "58745c13ad585" => [
            "_id" => "58745c13ad585",
            "email" => "a@site.com",
            "name" => "A",
            "score" => 80
        ],
        "58745c19b4c51" => [
            "_id" => "58745c19b4c51",
            "email" => "b@site.com",
            "name" => "B",
            "score" => 76
        ],
        "58745c1ef0b13" => [
            "_id" => "58745c1ef0b13",
            "email" => "c@site.com",
            "name" => "C",
            "score" => 95
        ]
    ];

    public function setUp()
    {
        $this->filepath = __DIR__.'/db/data.json';
        // initialize data
        file_put_contents($this->filepath, json_encode($this->dummyData));

        $this->db = new Collection($this->filepath);
    }

    public function testAll()
    {
        $result = $this->db->all();
        $this->assertEquals($result, array_values($this->dummyData));
    }

    public function testFind()
    {
        $result = $this->db->find('58745c19b4c51');
        $this->assertEquals($result, [
            "_id" => "58745c19b4c51",
            "email" => "b@site.com",
            "name" => "B",
            "score" => 76
        ]);
    }

    public function testFirst()
    {
        $result = $this->db->query()->first();
        $this->assertEquals($result, [
            "_id" => "58745c13ad585",
            "email" => "a@site.com",
            "name" => "A",
            "score" => 80
        ]);
    }

    public function testGetAll()
    {
        $this->assertEquals($this->db->query()->get(), array_values($this->dummyData));
    }

    public function testFilter()
    {
        $result = $this->db->where(function($row) {
            return $row['score'] > 90;
        })->get();

        $this->assertEquals($result, [
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testMap()
    {
        $result = $this->db->map(function($row) {
            return [
                'x' => $row['score']
            ];
        })->get();

        $this->assertEquals($result, [
            ["x" => 80],
            ["x" => 76],
            ["x" => 95],
        ]);
    }

    public function testGetSomeColumns()
    {
        $result = $this->db->query()->get(['email', 'name']);
        $this->assertEquals($result, [
            [
                "email" => "a@site.com",
                "name" => "A",
            ],
            [
                "email" => "b@site.com",
                "name" => "B",
            ],
            [
                "email" => "c@site.com",
                "name" => "C",
            ]
        ]);   
    }

    public function testSortBy()
    {
        $result = $this->db->query()->sortBy('score', 'desc')->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ],
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ],
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testSkip()
    {
        $result = $this->db->query()->skip(1)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ],
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testTake()
    {
        $result = $this->db->query()->take(1, 1)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testCount()
    {
        $this->assertEquals($this->db->count(), 3);
    }

    public function testSum()
    {
        $this->assertEquals($this->db->sum('score'), 76+80+95);
    }

    public function testAvg()
    {
        $this->assertEquals($this->db->avg('score'), (76+80+95)/3);
    }

    public function testMin()
    {
        $this->assertEquals($this->db->min('score'), 76);
    }

    public function testMax()
    {
        $this->assertEquals($this->db->max('score'), 95);
    }

    public function testLists()
    {
        $this->assertEquals($this->db->lists('score'), [80, 76, 95]);
    }

    public function testListsWithKey()
    {
        $result = $this->db->lists('score', 'email');
        $this->assertEquals($result, [
            'a@site.com' => 80, 
            'b@site.com' => 76, 
            'c@site.com' => 95
        ]);
    }

    public function testGetWhereEquals()
    {
        $result = $this->db->where('name', 'C')->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testGetWhereBiggerThan()
    {
        $result = $this->db->where('score', '>', 80)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testGetWhereBiggerThanEquals()
    {
        $result = $this->db->where('score', '>=', 80)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ],
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testGetWhereLowerThan()
    {
        $result = $this->db->where('score', '<', 80)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testGetWhereLowerThanEquals()
    {
        $result = $this->db->where('score', '<=', 80)->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ],
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testGetWhereIn()
    {
        $result = $this->db->where('score', 'in', [80])->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ]
        ]);
    }

    public function testGetWhereNotIn()
    {
        $result = $this->db->where('score', 'not in', [80])->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ],
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testGetWhereMatch()
    {
        $result = $this->db->where('email', 'match', '/^b@/')->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testGetWhereBetween()
    {
        $result = $this->db->where('score', 'between', [80, 95])->get();
        $this->assertEquals($result, [
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ],
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 95
            ]
        ]);
    }

    public function testInsert()
    {
        $this->db->insert([
            'test' => 'foo'
        ]);

        $this->assertEquals($this->db->count(), 4);
        $data = $this->db->where('test', 'foo')->first();
        $this->assertEquals(array_keys($data), ['_id', 'test']);
        $this->assertEquals($data['test'], 'foo');
    }

    public function testInserts()
    {
        $this->db->inserts([
            ['test' => 'foo'],
            ['test' => 'bar'],
            ['test' => 'baz']
        ]);

        $this->assertEquals($this->db->count(), 6);
    }

    public function testUpdate()
    {
        $this->db->where('score', '>=', 80)->update([
            'score' => 90
        ]);

        $this->assertEquals($this->db->all(), [
            [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 90
            ],
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ],
            [
                "_id" => "58745c1ef0b13",
                "email" => "c@site.com",
                "name" => "C",
                "score" => 90
            ],
        ]);
    }

    public function testUpdateWithFilterMapAndSave()
    {
        $this->db->where('score', '>=', 80)->map(function($row) {
            return [
                'x' => $row['score'] 
            ];
        })->save();

        $this->assertEquals($this->db->all(), [
            [
                "_id" => "58745c13ad585",
                "x" => 80
            ],
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ],
            [
                "_id" => "58745c1ef0b13",
                "x" => 95
            ],
        ]);
    }

    public function testDelete()
    {
        $this->db->where('score', '>=', 80)->delete();
        $this->assertEquals($this->db->all(), [
            [
                "_id" => "58745c19b4c51",
                "email" => "b@site.com",
                "name" => "B",
                "score" => 76
            ]
        ]);
    }

    public function testWithOne()
    {
        $result = $this->db->withOne($this->db, 'other', 'email', '=', 'email')->first();
        $this->assertEquals($result, [
            "_id" => "58745c13ad585",
            "email" => "a@site.com",
            "name" => "A",
            "score" => 80,
            'other' => [
                "_id" => "58745c13ad585",
                "email" => "a@site.com",
                "name" => "A",
                "score" => 80
            ],
        ]);
    }

    public function testWithMany()
    {
        $result = $this->db->withMany($this->db, 'other', 'email', '=', 'email')->first();
        $this->assertEquals($result, [
            "_id" => "58745c13ad585",
            "email" => "a@site.com",
            "name" => "A",
            "score" => 80,
            'other' => [
                [
                    "_id" => "58745c13ad585",
                    "email" => "a@site.com",
                    "name" => "A",
                    "score" => 80
                ]
            ],
        ]);
    }

    public function testSelectAs()
    {
        $result = $this->db->query()->withOne($this->db, 'other', 'email', '=', 'email')->first([
            'email',
            'other.email:other_email'
        ]);

        $this->assertEquals($result, [
            "email" => "a@site.com",
            "other_email" => "a@site.com",
        ]);
    }

    public function tearDown()
    {
        unlink($this->filepath);
    }

}
