# INSTALL
```
composer require akbarali/data-object
```

# USAGE
PHP Array
```php
$object = DataObj::createFromArray([
    'key1' => 'value1',
    'key2' => 'value2',
]);
```
Laravel model
```php
$model = User::query()->find(1);
$object = DataObj::createFromEloquentModel($model);
```
Json
```php
$object = DataObj::createFromJson('{"key1":"value1","key2":"value2"}');
```

# Laravel Raltionlarda ishlatish

Avval DataObjectlar yaratib olamiz `StoreData`
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
Endi modeldan obyekt yaratib olamiz
```php
$store = Store::query()->with(['user.role'])->find(1);
$storeData = StoreData::createFromEloquentModel($store);
```
Eslatma: Relationni ham DataObjectga o'girmoqchi bo'lsangiz modelga realiton ochib o'sha relationni with olishingiz kerak. Shunda U ham data objectga o'giriladi

Laravelda relationlar birga ko'p bo'lsa qanday ishlatish kerak?

ProductData
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
StoreData
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
```

# DataObjectni arrayga aylantirish
```php
$storeData->toArray();
//yoki
$storeData->all(true);
```
# DataObjectni SnakCasega aylantirish
```php
$storeData->toSnakeCase();
```


Modelda `created_at` odatda `use Illuminate\Support\Carbon;` bo'ladi. Siz `created_at` ham `Carbon` holatida o'tkazsangiz bo'ladi.
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
    'id' => 1,
    'full_name' => 'Akbarali',
]);
```
$object->id = 2;
**Error: Cannot modify readonly property App\DataObjects\HistoryData::$id**
