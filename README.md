# INSTALL

```
composer require akbarali/data-object
```

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

# 2.0 version supported Readonly Properties

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