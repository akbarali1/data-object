# INSTALL

```
composer require akbarali/data-object
```

# TODO

| Done ?               | Name                                                                         | Version       |
|:---------------------|:-----------------------------------------------------------------------------|:--------------|
| :white_check_mark:   | Add `createProperty`                                                         | 2.5           |
| :white_check_mark:   | Add command to create `DataObject` via `Eloquent Model` or `Database Tables` | 2.6.5         |
| :white_large_square: | Add command to create `DataObject` via PHP `Array`                           | In the future |
| :white_large_square: | Add creation without converting the `Eloquent Model` to an array.            | In the future |

# USAGE

Array create Data Object

```php
$object = DataObj::createFromArray([
    'key1' => 'value1',
    'key2' => 'value2',
]);
```

Laravel model create Data Object

```php
$model = User::query()->find(1);
$object = DataObj::createFromEloquentModel($model);
```

Json create Data Object

```php
$object = DataObj::createFromJson('{"key1":"value1","key2":"value2"}');
```

# Laravel Relation BelongsTo or HasOne

Create `StoreData`

```php
class StoreData extends \Akbarali\DataObject\DataObjectBase
{
    public ?string $id;
    public ?int    $user_id;
    public ?string $name;
    public ?string $phone;
    public ?string $address;
    public ?string $description = "Default Description";
    public ?string $image;
    public ?bool   $status;
    public ?string $created_at;

    public UserData $user;
}
```

`UserData`

```php
class UserData extends \Akbarali\DataObject\DataObjectBase
{
    public ?int    $id;
    public ?string $name;
    public ?string $email;
    public ?string $avatar;
    public ?string $phone;
    public ?string $birth_date;
    public ?bool   $status;
    public ?string $created_at;

    public RoleData $role;
}
```

`RoleData`

```php
class RoleData extends \Akbarali\DataObject\DataObjectBase
{
    public int    $id;
    public string $name;
    public string $slug;
    public string $created_at;
}
```

```php
$store = Store::query()->with(['user.role'])->find(1);
$storeData = StoreData::createFromEloquentModel($store);
```

Note: If you want to turn the relation into a DataObject, you should open a realton to the model and get that relation with. Then U is also converted to data object

# Laravel Relation HasMany

`ProductData`

```php
ProductData extends \Akbarali\DataObject\DataObjectBase
{
    public ?int    $id;
    public ?string $name;
    public ?string $description;
    public ?string $image;
    public ?bool   $status;
    public ?string $created_at;
}
```

`StoreData`

```php
StoreData extends \Akbarali\DataObject\DataObjectBase
{
    public ?int    $id;
    public ?string $name;
    public ?string $description;
    public ?string $image;
    public ?bool   $status;
    public ?string $created_at;
    
    public array|ProductData $products;
}
```

```php
$store = Store::query()->with(['products'])->find(1);
$storeData = StoreData::createFromEloquentModel($store);
//or
$storeData = StoreData::fromModel($store);
```

# DataObject To array

```php
$storeData->toArray();
//or
$storeData->all(true);
```

# DataObjec To SnakCase

```php
$storeData->toSnakeCase();
```

In a model, `created_at` is usually `use Illuminate\Support\Carbon;`. You can also pass `created_at` to `Carbon`.

```php
use Illuminate\Support\Carbon;

ProductData extends \Akbarali\DataObject\DataObjectBase
{
    public int    $id;
    public string $name;
    public string $description;
    public string $image;
    public bool   $status;
    public Carbon $created_at;
}
```

# 2.0 version

Supported Readonly Properties

```php
class ClientData extends \Akbarali\DataObject\DataObjectBase
{
    public readonly int $id;
    public string       $full_name;
}
```

```php
$object = DataObj::createFromArray([
    'id'        => 1,
    'full_name' => 'Akbarali',
]);
$object->id = 2;
```

**Error: Cannot modify readonly property App\DataObjects\HistoryData::$id**

# 2.2 version

Adding: `fromModel` `fromJson` `fromArray`

Add static function `arrayToClassProperty`

```php
class ClientData extends \Akbarali\DataObject\DataObjectBase
{
    public int    $id;
    public string $full_name;
}
```

```php
$object = ClientData::arrayToClassProperty([
    'id'        => 1,
    'full_name' => 'Akbarali',
]); 
```

Return string: `public readonly int $id;public string $full_name;`

# 2.5 version

`createProperty` method added to create a property return string and bug fix `arrayToClassProperty`

# 2.6.2 version

Add command create DataObject

Install

Add `DataObjectProvider` to `/config/app.php` `providers`

```php
'providers' => [
...
\Akbarali\DataObject\DataObjectProvider::class,
]
```

DataObject create console command

```
 php artisan do:create
```

Models Create DataObject

```
 php artisan do:create models
```

Tables Create DataObject

```
 php artisan do:create tables
```

Search Table and create DataObject

```
 php artisan do:create tableSearch
```

Search Model and create DataObject

```
 php artisan do:create modelSearch
```

# 2.6.5 version

Add `--model` and `--table` options to create DataObject

```
php artisan do:create --model="App\Models\QQB\User"
```

```
php artisan do:create --table="users"
```

# 2.8.2 version

Add `toArrayForgetProperty` add new method. 
