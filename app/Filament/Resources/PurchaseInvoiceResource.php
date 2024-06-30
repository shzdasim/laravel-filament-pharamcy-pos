<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';


   public static function getNavigationBadge(): ?string
   {
       return static::getModel()::count();
   }


   protected static ?string $navigationLabel = 'PURCHASE INVOICES';
   protected static ?string $navigationGroup = 'INVOICES';
   protected static ?string $modelLabel = 'Purchase Invoice';
   protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->required()
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->native(false)
                            ->preload(),
                        Forms\Components\TextInput::make('posted_number')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->default(PurchaseInvoice::generateCode()),
                        Forms\Components\DatePicker::make('posted_date')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        Forms\Components\TextInput::make('remarks')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_amount')
                            ->required()
                            ->numeric()
                            ->rules([
                                fn(Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $invoice_amount = $get('invoice_amount');
                                    $total_amount = $get('total_amount');
                                    if (abs($invoice_amount - $total_amount) > 5) {
                                        $fail('Invoice amount must not differ from the total amount by more than 5.');
                                    }
                                }
                            ]),
                    ])->columns(3),

                Forms\Components\Section::make()
                    ->schema([
                        Repeater::make('purchaseInvoiceItems')
                            ->relationship('purchaseInvoiceItems')
                            ->label('Purchase Invoice Items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->label('Product')
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('pack_purchase_price', $product->pack_purchase_price);
                                                $set('pack_sale_price', $product->pack_sale_price);
                                                $set('unit_purchase_price', $product->unit_purchase_price);
                                                $set('unit_sale_price', $product->unit_sale_price);
                                                $set('pack_size', $product->pack_size);
                                            } else {
                                                $set('pack_purchase_price', null);
                                                $set('pack_sale_price', null);
                                                $set('unit_purchase_price', null);
                                                $set('unit_sale_price', null);
                                                $set('pack_size', null);
                                            }
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('pack_size')->readOnly(),
                                Forms\Components\TextInput::make('pack_quantity')
                                    ->label('P.Quantity')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $unit_quantity = $state * $pack_size;
                                        $set('unit_quantity', $unit_quantity);
                                        $set('quantity', $unit_quantity + ((int) $get('unit_bonus') ?? 0));
                                        
                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);
                                        
                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        // Calculate margin last
                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('unit_quantity')
                                    ->label('U.Quantity')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        if ($pack_size > 0) {
                                            $pack_quantity = $state / $pack_size;
                                            $set('pack_quantity', $pack_quantity);
                                        }
                                        $set('quantity', $state + ((int) $get('unit_bonus') ?? 0));

                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $state * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);
                                      

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;

                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($state + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('pack_purchase_price')
                                    ->label('P.Pur.Price')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        if ($pack_size > 0) {
                                            $unit_purchase_price = $state / $pack_size;
                                            $set('unit_purchase_price', $unit_purchase_price);
                                        }

                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('unit_purchase_price')
                                    ->label('U.Pur.Price')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $pack_purchase_price = $state * $pack_size;
                                        $set('pack_purchase_price', $pack_purchase_price);

                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $state;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('pack_sale_price')
                                    ->label('P.Sale.Price')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        if ($pack_size > 0) {
                                            $unit_sale_price = $state / $pack_size;
                                            $set('unit_sale_price', $unit_sale_price);
                                        }

                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('unit_sale_price')
                                    ->label('U.Sale.Price')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $pack_sale_price = $state * $pack_size;
                                        $set('pack_sale_price', $pack_sale_price);

                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('pack_bonus')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        if ($pack_size > 0) {
                                            $unit_bonus = $state * $pack_size;
                                            $set('unit_bonus', $unit_bonus);
                                        }
                                        $set('quantity', ((int) $get('unit_quantity') ?? 0) + $unit_bonus);

                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + $unit_bonus)) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('unit_bonus')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pack_size = (int) $get('pack_size') ?? 0;
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $pack_bonus = $state / $pack_size;
                                        $set('pack_bonus', $pack_bonus);
                                        $set('quantity', ((int) $get('unit_quantity') ?? 0) + $state);

                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $item_discount = (float) $get('item_discount_percentage') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $item_discount) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + $state)) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('item_discount_percentage')
                                    ->label('Disc%')
                                    ->numeric()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $pack_size = (int) $get('pack_size');
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * $state) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('margin')->numeric()->readOnly(),
                                Forms\Components\TextInput::make('sub_total')->numeric()->required()->readOnly()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $unit_purchase_price = (float) $get('unit_purchase_price') ?? 0;
                                        $unit_quantity = (int) $get('unit_quantity') ?? 0;
                                        $pack_size = (int) $get('pack_size');
                                        $pack_quantity = (int) $get('pack_quantity') ?? 0;
                                        $unit_bonus = (int) $get('unit_bonus') ?? 0;
                                        $total_cost = $unit_quantity * $unit_purchase_price;
                                        $discount_amount = ($total_cost * ((float) $get('item_discount_percentage') ?? 0)) / 100;
                                        $total_cost_with_discount = $total_cost - $discount_amount;
                                        $set('sub_total', $total_cost_with_discount);

                                        $pack_purchase_price = (float) $get('pack_purchase_price') ?? 0;
                                        $item_discount_percentage = (float) $get('item_discount_percentage') ?? 0;
                                
                                        // Calculate total effective quantity
                                        $total_quantity = ($pack_size * $pack_quantity) + $unit_bonus;

                                        if ($total_quantity > 0) {
                                            // Calculate average price
                                            $avg_price = ($pack_quantity * $pack_purchase_price) / $total_quantity;
                                            
                                            // Apply discount
                                            $avg_price_with_discount = $avg_price * (1 - $item_discount_percentage / 100);
                                
                                            $set('avg_price', $avg_price_with_discount);
                                        } else {
                                            $set('avg_price', 0);
                                        }

                                        $unit_sale_price = (float) $get('unit_sale_price') ?? 0;
                                        $margin = ($unit_sale_price > 0) ? (($unit_sale_price - $total_cost_with_discount / ($unit_quantity + ((int) $get('unit_bonus') ?? 0))) / $unit_sale_price) * 100 : 0;
                                        $set('margin', $margin);

                                        // Update total amount without discount
                                        $total_amount = collect($get('../../purchaseInvoiceItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);
                                        $set('../../original_total_amount', $total_amount);

                                        // Recalculate the final total amount considering the discount and tax on total
                                        $overall_discount = (float) $get('../../discount_percentage') ?? 0;
                                        $tax = (float) $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total_amount', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('avg_price')->numeric()->readOnly(),
                                Forms\Components\TextInput::make('quantity')->numeric()->required()->readOnly(),
                            ])
                            ->columns(9),
                    ]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('Tax%')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $original_total_amount = collect($get('purchaseInvoiceItems'))
                                    ->sum(fn($item) => $item['sub_total'] ?? 0);
                                $discount_amount = ($original_total_amount * ((float) $get('discount_percentage') ?? 0)) / 100;
                                $total_with_discount = $original_total_amount - $discount_amount;
                                $tax_amount = ($total_with_discount * $state) / 100;
                                $set('tax_amount', $tax_amount);
                                $total_with_tax = $total_with_discount + $tax_amount;
                                $set('total_amount', $total_with_tax);
                            }),
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $original_total_amount = collect($get('purchaseInvoiceItems'))
                                    ->sum(fn($item) => $item['sub_total'] ?? 0);
                                $discount_amount = ($original_total_amount * ((float) $get('discount_percentage') ?? 0)) / 100;
                                $total_with_discount = $original_total_amount - $discount_amount;
                                $tax_percentage = ($total_with_discount > 0) ? ($state / $total_with_discount) * 100 : 0;
                                $set('tax_percentage', $tax_percentage);
                                $total_with_tax = $total_with_discount + $state;
                                $set('total_amount', $total_with_tax);
                            }),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Discount %')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $original_total_amount = collect($get('purchaseInvoiceItems'))
                                    ->sum(fn($item) => $item['sub_total'] ?? 0);
                                $discount_amount = ($original_total_amount * $state) / 100;
                                $set('discount_amount', $discount_amount);
                                $tax_percentage = (float) $get('tax_percentage') ?? 0;
                                $total_with_discount = $original_total_amount - $discount_amount;
                                $total_with_tax = $total_with_discount + ($total_with_discount * $tax_percentage / 100);
                                $set('total_amount', $total_with_tax);
                            }),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $original_total_amount = collect($get('purchaseInvoiceItems'))
                                    ->sum(fn($item) => $item['sub_total'] ?? 0);
                                $discount_percentage = ($original_total_amount > 0) ? ($state / $original_total_amount) * 100 : 0;
                                $set('discount_percentage', $discount_percentage);
                                $tax_percentage = (float) $get('tax_percentage') ?? 0;
                                $total_with_discount = $original_total_amount - $state;
                                $total_with_tax = $total_with_discount + ($total_with_discount * $tax_percentage / 100);
                                $set('total_amount', $total_with_tax);
                            }),
                        Forms\Components\TextInput::make('original_total_amount')
                            ->numeric()
                            ->reactive()
                            ->hidden(),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->reactive()
                            ->readOnly(),
                    ])->columns(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_id')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('posted_number')->searchable(),
                Tables\Columns\TextColumn::make('posted_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')->searchable(),
                Tables\Columns\TextColumn::make('invoice_amount')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('tax_percentage')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('tax_amount')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'view' => Pages\ViewPurchaseInvoice::route('/{record}'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}
