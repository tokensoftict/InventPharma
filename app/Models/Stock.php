<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Classes\Settings;
use App\Traits\ModelFilterTraits;
use App\Traits\StockModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stock
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $code
 * @property int|null $category_id
 * @property int|null $manufacturer_id
 * @property int|null $classification_id
 * @property int|null $stockgroup_id
 * @property int|null $brand_id
 * @property float|null $whole_price
 * @property float|null $bulk_price
 * @property float|null $retail_price
 * @property float|null $cost_price
 * @property float|null $retail_cost_price
 * @property int $wholesales
 * @property int $bulksales
 * @property int $retail
 * @property int $quantity
 * @property string|null $barcode
 * @property string|null $location
 * @property string|null $image_path
 * @property string|null $image_download_status
 * @property bool $expiry
 * @property int $piece
 * @property int $box
 * @property int $carton
 * @property bool $sachet
 * @property bool $status
 * @property bool $image_uploaded
 * @property int|null $batched
 * @property bool $reorder
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Brand|null $brand
 * @property Category|null $category
 * @property Classification|null $classification
 * @property Manufacturer|null $manufacturer
 * @property Stockgroup|null $stockgroup
 * @property User|null $user
 * @property Collection|Batchstock[] $batchstocks
 * @property Collection|Invoiceitembatch[] $invoiceitembatches
 * @property Collection|Invoiceitem[] $invoiceitems
 * @property Collection|Movingstock[] $movingstocks
 * @property Collection|Nearoutofstock[] $nearoutofstocks
 * @property Collection|PromotionItem[] $promotion_items
 * @property Collection|Purchaseitem[] $purchaseitems
 * @property Collection|Retailnearoutofstock[] $retailnearoutofstocks
 * @property Collection|Stockbatch[] $stockbatches
 * @property Collection|Stockbincard[] $stockbincards
 * @property Collection|Stockopening[] $stockopenings
 * @property Collection|Stocktransferitem[] $stocktransferitems
 * @property Collection|stockbarcodes[] $stockbarcodes
 * @package App\Models
 */
class Stock extends Model
{
    use ModelFilterTraits, StockModelTrait;

	protected $table = 'stocks';

   // protected $with = ['activeBatches', 'stockbatches'];

	protected $casts = [
		'category_id' => 'int',
		'manufacturer_id' => 'int',
		'classification_id' => 'int',
		'stockgroup_id' => 'int',
		'brand_id' => 'int',
		'whole_price' => 'float',
		'bulk_price' => 'float',
		'retail_price' => 'float',
		'cost_price' => 'float',
		'retail_cost_price' => 'float',
		'wholesales' => 'int',
		'bulksales' => 'int',
		'retail' => 'int',
		'quantity' => 'int',
		'piece' => 'int',
		'box' => 'int',
        'retail_store' => 'int',
		'carton' => 'int',
		'status' => 'bool',
		'batched' => 'int',
		'reorder' => 'bool',
		'user_id' => 'int'
	];


    protected $appends = ['has_promo'];

	protected $fillable = [
		'name',
		'description',
		'code',
		'category_id',
		'manufacturer_id',
		'classification_id',
		'stockgroup_id',
		'brand_id',
		'whole_price',
		'bulk_price',
		'retail_price',
		'cost_price',
		'retail_cost_price',
		'wholesales',
		'bulksales',
		'retail',
		'quantity',
		'barcode',
		'location',
		'expiry',
		'piece',
		'box',
        'retail_store',
		'carton',
		'sachet',
		'status',
		'batched',
		'reorder',
		'user_id',
        'image_path',
        'image_uploaded',
        'image_download_status',
        'minimum_quantity'
	];

    protected $with = ['promotion_items'];

	public function brand()
	{
		return $this->belongsTo(Brand::class);
	}


    public function getImagePathAttribute()
    {
        if($this->attributes['image_path'] == NULL) {
            return "logo/" . app(Settings::class)->store()->logo;
        }
        return $this->attributes['image_path'];
    }

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function classification()
	{
		return $this->belongsTo(Classification::class);
	}

	public function manufacturer()
	{
		return $this->belongsTo(Manufacturer::class);
	}

	public function stockgroup()
	{
		return $this->belongsTo(Stockgroup::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function stockbatches()
	{
		return $this->hasMany(Stockbatch::class);
	}

	public function stocktransferitems()
	{
		return $this->hasMany(Stocktransferitem::class);
	}


    public function stockOpenings()
    {
        return $this->hasMany(Stockopening::class);
    }

    public function stockOpening()
    {
        if($this->stockOpenings()->where('date_added',date('Y-m-d'))->count() == 0){
            if($this->stockOpenings()->where('date_added',yesterdayDate())->count() > 0){
                return $this->stockOpenings()->where('date_added',yesterdayDate());
            }else{
                return NULL;
            }
        }
        return $this->stockOpenings()->where('date_added',date('Y-m-d'));
    }


    public function nearOsOne()
    {
        return $this->hasOne(Nearoutofstock::class);
    }


    public function invoiceitembatches()
    {
        return $this->hasMany(Invoiceitembatch::class);
    }

    public function invoiceitems()
    {
        return $this->hasMany(Invoiceitem::class);
    }


    public function movingstocks()
    {
        return $this->hasMany(Movingstock::class);
    }

    public function nearoutofstocks()
    {
        return $this->hasMany(Nearoutofstock::class);
    }


    public function promoitems()
    {
        return $this->hasMany(Promoitem::class);
    }

    public function purchaseitems()
    {
        return $this->hasMany(Purchaseitem::class);
    }

    public function retailnearoutofstocks()
    {
        return $this->hasMany(Retailnearoutofstock::class);
    }


    public function stockbincards()
    {
        return $this->hasMany(Stockbincard::class);
    }

    public function batchstocks()
    {
        return $this->hasMany(Batchstock::class);
    }

    public function batchstock()
    {
        return $this->hasOne(Batchstock::class);
    }


    public function promotion_items()
    {
        return $this->hasMany(PromotionItem::class);
    }

    public function promotion_item()
    {
        return $this->hasOne(PromotionItem::class)->where('status_id', status('Approved'))->orderBy('id', 'DESC');
    }


    public function stockbarcodes()
    {
        return $this->hasMany(Stockbarcode::class);
    }


    public function stockquantityprices()
    {
        return $this->hasMany(ProductCustomPrice::class);
    }
}
