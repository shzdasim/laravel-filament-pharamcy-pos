<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleInvoiceResource\Pages;
use App\Filament\Resources\SaleInvoiceResource\RelationManagers;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleInvoiceResource extends Resource
{
    protected static ?string $model = SaleInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationLabel = 'SALE INVOICES';
    protected static ?string $navigationGroup = 'INVOICES';
    protected static ?string $modelLabel = 'Sale Invoice';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        $products = Product::all();

        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->required()
                            ->default(auth()->user()->id),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->default(function () {
                                $defaultCustomer = Customer::where('id', 1)->first();
                                return $defaultCustomer ? $defaultCustomer->id : null;
                            }),
                        Forms\Components\TextInput::make('posted_number')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->default(SaleInvoice::generateCode()),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])->columns(3),
                Forms\Components\Section::make()
                    ->schema([
                        Repeater::make('saleInvoiceItems')
                            ->relationship('saleInvoiceItems')
                            ->label('Sale Invoice Items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Select Product')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->allowHtml()
                                    ->getOptionLabelUsing(function ($value) {
                                        $product = Product::find($value);
                                        return $product ? $product->name : '';
                                    })
                                    ->getSearchResultsUsing(function (string $search = null) {
                                        $query = Product::query();
                                        
                                        if ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('quantity', 'like', "%{$search}%")
                                                ->orWhere('unit_sale_price', 'like', "%{$search}%")
                                                ->orWhere('unit_purchase_price', 'like', "%{$search}%")
                                                ->orWhere('avg_price', 'like', "%{$search}%");
                                        }
                                
                                        return $query->get()
                                            ->mapWithKeys(function ($product) {
                                                return [$product->id => "
                                                    <div>
                                                        <strong>{$product->name}</strong><br>
                                                        <span>Quantity: {$product->quantity}</span><br>
                                                        <span>Sale Price: {$product->unit_sale_price}</span><br>
                                                        <span>Purchase Price: {$product->unit_purchase_price}</span><br>
                                                        <span>Avg Price: {$product->avg_price}</span>
                                                    </div>
                                                "];
                                            });
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('current_quantity', $product->quantity);
                                                $set('price', $product->unit_sale_price);
                                                $set('pack_size', $product->pack_size);
                                                $set('../../product_margin', $product->margin); // Update the margin in summary section
                                            } else {
                                                $set('current_quantity', 0);
                                                $set('price', 0);
                                                $set('../../product_margin', null); // Reset the margin in summary section
                                            }
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('pack_size')->readonly(),
                                Forms\Components\TextInput::make('current_quantity')
                                    ->label('CURRENT.Q')
                                    ->required()
                                    ->numeric()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $current_quantity = $get('current_quantity') ?? 0;
                                        $price = $get('price') ?? 0;
                                        $item_discount = $get('item_discount_percentage') ?? 0;
                                        $quantity = $state ?? 0;
                                        $sub_total = $price * $quantity;
                                        $discount_amount = ($sub_total * $item_discount) / 100;
                                        $sub_total_with_discount = $sub_total - $discount_amount;
                                        $set('sub_total', $sub_total_with_discount);

                                        // Calculate gross amount and item discount
                                        $gross_amount = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['price'] ?? 0) * floatval($item['quantity'] ?? 0));
                                        $item_discount_total = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => (floatval($item['price'] ?? 0) * floatval($item['quantity'] ?? 0) * floatval($item['item_discount_percentage'] ?? 0)) / 100);

                                        $total_amount = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));

                                        $set('../../gross_amount', $gross_amount);
                                        $set('../../item_discount', $item_discount_total);
                                        $set('../../original_total_amount', $total_amount);

                                        $overall_discount = $get('../../discount_percentage') ?? 0;
                                        $tax = $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total', $total_with_tax);
                                    })
                                    ->rules([
                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $current_quantity = $get('current_quantity') ?? 0;
                                            if ($value > $current_quantity) {
                                                $fail('The quantity cannot exceed the available stock.');
                                            }
                                        }
                                    ]),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('item_discount_percentage')
                                    ->label('DISC%')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $item_discount = $state ?? 0;
                                        $price = $get('price') ?? 0;
                                        $quantity = $get('quantity') ?? 0;
                                        $sub_total = $price * $quantity;
                                        $discount_amount = ($sub_total * $item_discount) / 100;
                                        $sub_total_with_discount = $sub_total - $discount_amount;
                                        $set('sub_total', $sub_total_with_discount);

                                        // Calculate gross amount and item discount
                                        $gross_amount = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['price'] ?? 0) * floatval($item['quantity'] ?? 0));
                                        $item_discount_total = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => (floatval($item['price'] ?? 0) * floatval($item['quantity'] ?? 0) * floatval($item['item_discount_percentage'] ?? 0)) / 100);

                                        $total_amount = collect($get('../../saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));

                                        $set('../../gross_amount', $gross_amount);
                                        $set('../../item_discount', $item_discount_total);
                                        $set('../../original_total_amount', $total_amount);

                                        $overall_discount = $get('../../discount_percentage') ?? 0;
                                        $tax = $get('../../tax_percentage') ?? 0;
                                        $total_with_discount = $total_amount - ($total_amount * $overall_discount / 100);
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('../../total', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('sub_total')
                                    ->required()
                                    ->numeric()
                                    ->readOnly(),
                            ])->columns(9)
                            ->reactive()
                            ->addActionLabel('Add Product'),
                    ]),
                Section::make()
                    ->schema([
                        Forms\Components\Section::make('Summary')
                            ->schema([
                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('Discount %')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $discount = $state ?? 0;
                                        $original_total_amount = collect($get('saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));
                                        $discount_amount = ($original_total_amount * $discount) / 100;
                                        $set('discount_amount', $discount_amount);

                                        $tax = $get('tax_percentage') ?? 0;
                                        $total_with_discount = $original_total_amount - $discount_amount;
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('total', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Disc. Amount')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $discount_amount = $state ?? 0;
                                        $original_total_amount = collect($get('saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));
                                        $discount_percentage = ($original_total_amount > 0) ? ($discount_amount / $original_total_amount) * 100 : 0;
                                        $set('discount_percentage', $discount_percentage);

                                        $tax = $get('tax_percentage') ?? 0;
                                        $total_with_discount = $original_total_amount - $discount_amount;
                                        $total_with_tax = $total_with_discount + ($total_with_discount * $tax / 100);
                                        $set('total', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('tax_percentage')
                                    ->label('Tax %')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $tax = $state ?? 0;
                                        $original_total_amount = collect($get('saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));
                                        $total_with_discount = $original_total_amount - ($original_total_amount * ($get('discount_percentage') ?? 0) / 100);
                                        $tax_amount = ($total_with_discount * $tax) / 100;
                                        $set('tax_amount', $tax_amount);

                                        $total_with_tax = $total_with_discount + $tax_amount;
                                        $set('total', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->numeric()
                                    ->reactive()
                                    ->debounce(500) // Debounce added here
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $tax_amount = $state ?? 0;
                                        $original_total_amount = collect($get('saleInvoiceItems'))
                                            ->sum(fn($item) => floatval($item['sub_total'] ?? 0));
                                        $discount_amount = $get('discount_amount') ?? 0;
                                        $total_with_discount = $original_total_amount - $discount_amount;
                                        $tax_percentage = ($total_with_discount > 0) ? ($tax_amount / $total_with_discount) * 100 : 0;
                                        $set('tax_percentage', $tax_percentage);

                                        $total_with_tax = $total_with_discount + $tax_amount;
                                        $set('total', $total_with_tax);
                                    }),
                                Forms\Components\TextInput::make('gross_amount')
                                    ->label('G. Amnt')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->reactive(),
                                Forms\Components\TextInput::make('item_discount')
                                    ->label('Item Disc')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->reactive(),
                                Forms\Components\TextInput::make('original_total_amount')
                                    ->numeric()
                                    ->reactive()
                                    ->hidden(),
                                Forms\Components\TextInput::make('total')
                                    ->required()
                                    ->numeric()
                                    ->reactive()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('product_margin')
                                    ->label('Margin')
                                    ->readOnly()
                                    ->numeric(),
                            ])->columns(8),
                    ]),
            ])->extraAttributes(['onkeydown' => 'return event.key != "Enter";']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListSaleInvoices::route('/'),
            'create' => Pages\CreateSaleInvoice::route('/create'),
            'view' => Pages\ViewSaleInvoice::route('/{record}'),
            'edit' => Pages\EditSaleInvoice::route('/{record}/edit'),
        ];
    }
}
