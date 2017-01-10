
LaciDB - Flat File JSON DBMS
======================================

## Examples

#### Initialize

```php
use Emsifa\Laci\Collection;

$collection = new Collection(__DIR__.'/db/users.json');
```

#### Insert Data

```php
$user = $collection->insert([
    'name' => 'John Doe',
    'email' => 'johndoe@mail.com',
    'password' => password_hash('password', PASSWORD_BCRYPT)
]);
```

`$user` would be something like:

```php
[
    '_id' => '58745c13ad585',
    'name' => 'John Doe',
    'email' => 'johndoe@mail.com',
    'password' => '$2y$10$eMF03850wE6uII7UeujyjOU5Q2XLWz0QEZ1A9yiKPjbo3sA4qYh1m'
]
```

> '_id' is uniqid()

#### Find Single Record By ID

```php
$user = $collection->find('58745c13ad585');
```

#### Find One

```php
$user = $collection->where('email', 'johndoe@mail.com')->first();
```

#### Select All

```php
$data = $collection->all();
```

#### Update

```php
$collection->where('email', 'johndoe@mail.com')->update([
    'name' => 'John',
    'sex' => 'male'
]);
```

> Return value is count affected records

#### Delete

```php
$collection->where('email', 'johndoe@mail.com')->delete();
```

> Return value is count affected records

#### Multiple Inserts

```php
$bookCollection = new Collection('db/books.json');

$bookCollection->inserts([
    [
        'title' => 'Foobar',
        'published_at' => '2016-02-23',
        'author' => [
            'name' => 'John Doe',
            'email' => 'johndoe@mail.com'
        ],
        'star' => 3,
        'views' => 100
    ],
    [
        'title' => 'Bazqux',
        'published_at' => '2014-01-10',
        'author' => [
            'name' => 'Jane Doe',
            'email' => 'janedoe@mail.com'
        ],
        'star' => 5,
        'views' => 56
    ],
    [
        'title' => 'Lorem Ipsum',
        'published_at' => '2013-05-12',
        'author' => [
            'name' => 'Jane Doe',
            'email' => 'janedoe@mail.com'
        ],
        'star' => 4,
        'views' => 96
    ],
]);

```

#### Find Where

```php
// select * from book.json where author[name] = 'Jane Doe'
$bookCollection->where('author.name', 'Jane Doe')->get();

// select * from book.json where star > 3
$bookCollection->where('star', '>', 3)->get();

// select * from book.json where star > 3 AND author[name] = Jane Doe
$bookCollection->where('star', '>', 3)->where('author.name', 'Jane Doe')->get();
```

> Operator can be '=', '<', '<=', '>', '>=', 'in', 'not in', 'between', 'match'.

#### Implementing `OR` Using Filter

```php
$bookCollection->filter(function($row) {
    return $row['star'] > 3 OR $row['author.name'] == 'Jane Doe';
})->get();
```

> `$row['author.name']` is equivalent with `$row['author']['name']`

#### Select Specify Keys

```php
// select author, title from book.json where star > 3
$bookCollection->where('star', '>', 3)->get(['author.name', 'title']);
```

#### Select As

```php
// select author[name] as author_name, title from book.json where star > 3
$bookCollection->where('star', '>', 3)->get(['author.name:author_name', 'title']);
```

#### Mapping

```php
$bookCollection->map(function($row) {
    $row['score'] = $row['star'] + $row['views'];
    return $row;
})
->sortBy('score', 'desc')
->get();
```

#### Sorting

```php
// select * from book.json order by star asc
$bookCollection->sortBy('star')->get();

// select * from book.json order by star desc
$bookCollection->sortBy('star', 'desc')->get();

// custom sorting 
$bookCollection->sort(function($a, $b) {
    return $a['star'] < $b['star'] ? -1 : 1;
})->get();
```

#### Limit & Offset

```php
// select * from book.json offset 4
$bookCollection->skip(4)->get();

// select * from book.json limit 10 offset 4
$bookCollection->limit(10, 4)->get();
```

#### Join

```php
$userCollection = new Collection('db/users.json');
$bookCollection = new Collection('db/books.json');

// get user with 'books'
$userCollection->withMany($bookCollection, 'books', 'author.email', '=', 'email')->get();

// get books with 'user'
$bookCollection->withOne($userCollection, 'user', 'email', '=', 'author.email')->get();
```

#### Map and Save

```php
$bookCollection->where('star', '>', 3)->map(function($row) {
    $row['star'] = $row['star'] += 2;
    return $row;
})->save();
```

#### Transaction

```php
$bookCollection->begin();

try {

    // insert, update, delete, etc 
    // will stored into variable (memory)

    $bookCollection->commit(); // until this

} catch(Exception $e) {

    $bookCollection->rollback();

}
```

