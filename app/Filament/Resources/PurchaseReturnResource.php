<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseReturnResource\Pages;
use App\Filament\Resources\PurchaseReturnResource\RelationManagers;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-minus';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationLabel = 'PURCHASE RETURNS';
    protected static ?string $navigationGroup = 'RETURNS';
    protected static ?string $modelLabel = 'Purchase Return';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->required()
                            ->default(auth()->user()->id),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->preload(),
                        Forms\Components\Select::make('purchase_invoice_id')
                            ->label('Purchase Invoice')
                            ->relationship('purchaseInvoice', 'posted_number')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $purchaseInvoice = PurchaseInvoice::find($state);
                                    $set('supplier_id', $purchaseInvoice->supplier_id);
                                    $set('discount_percentage', $purchaseInvoice->discount_percentage);
                                    $set('tax_percentage', $purchaseInvoice->tax_percentage);
                                    $set('discount_amount', $purchaseInvoice->discount_amount);
                                    $set('tax_amount', $purchaseInvoice->tax_amount);
                                } else {
                                    $set('supplier_id', 0);
                                    $set('discount_percentage', 0);
                                    $set('tax_percentage', 0);
                                    $set('discount_amount', 0);
                                    $set('tax_amount', 0);
                                }
                            }),
                        Forms\Components\TextInput::make('posted_number')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->default(PurchaseReturn::generateCode()),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])->columns(4),
                Forms\Components\Section::make()
                    ->schema([
                        Repeater::make('purchaseReturnItems')
                            ->relationship('purchaseReturnItems')
                            ->label('Purchase Return Items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->reactive()
                                    ->options(function (callable $get) {
                                        $purchaseInvoiceId = $get('../../purchase_invoice_id');
                                        if ($purchaseInvoiceId) {
                                            $purchaseInvoice = PurchaseInvoice::find($purchaseInvoiceId);
                                            return $purchaseInvoice->purchaseInvoiceItems->pluck('product.name', 'product.id');
                                        }
                                        return Product::all()->pluck('name', 'id');
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $purchaseInvoiceId = $get('../../purchase_invoice_id');
                                        if ($purchaseInvoiceId && $state) {
                                            $purchaseInvoiceItem = PurchaseInvoiceItem::where('purchase_invoice_id', $purchaseInvoiceId)
                                                ->where('product_id', $state)
                                                ->first();
                                            if ($purchaseInvoiceItem) {
                                                $set('purchase_quantity', $purchaseInvoiceItem->unit_quantity);
                                                $set('unit_purchase_price', $purchaseInvoiceItem->unit_purchase_price);
                                                $set('item_discount_percentage', $purchaseInvoiceItem->item_discount_percentage);
                                            }
                                        } else if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_purchase_price', $product->unit_purchase_price);
                                            }
                                            $set('purchase_quantity', null);
                                            $set('item_discount_percentage', null);
                                        } else {
                                            $set('purchase_quantity', null);
                                            $set('purchase_price', null);
                                            $set('item_discount_percentage', null);
                                        }
                                    })->columnSpan(3),
                                Forms\Components\TextInput::make('purchase_quantity')
                                    ->label('PURCHASE.Q')
                                    ->numeric()
                                    ->readOnly()
                                    ->nullable(),
                                Forms\Components\TextInput::make('return_quantity')
                                    ->label('RETURN.Q')
                                    ->required()
                                    ->numeric()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = $get('unit_purchase_price') ?? 0;
                                        $item_discount = $get('item_discount_percentage') ?? 0;
                                        $quantity = $state ?? 0;
                                        $sub_total = $price * $quantity;
                                        $discount_amount = ($sub_total * $item_discount) / 100;
                                        $sub_total_with_discount = $sub_total - $discount_amount;
                                        $set('sub_total', $sub_total_with_discount);

                                        // Calculate gross amount and item discount
                                        $gross_total = collect($get('../../purchaseReturnItems'))
                                            ->sum(fn($item) => ($item['unit_purchase_price'] ?? 0) * ($item['return_quantity'] ?? 0));

                                        $total_amount = collect($get('../../purchaseReturnItems'))
                                            ->sum(fn($item) => $item['sub_total'] ?? 0);

                                        $set('../../gross_total', $gross_total);
                                        $set('../../original_total_amount', $total_amount);

                                        $overall_discount = $get('../../discount_percentage') ?? 0;
                                        $overall_discount_amount = ($total_amount * $overall_discount) / 100;
                                        $total_after_discount = $total_amount - $overall_discount_amount;

                                        $overall_tax = $get('../../tax_percentage') ?? 0;
                                        $overall_tax_amount = ($total_after_discount * $overall_tax) / 100;
                                        $total = $total_after_discount + $overall_tax_amount;
                                        $set('../../total', $total);
                                    }),
                                Forms\Components\TextInput::make('unit_purchase_price')
                                    ->label('PURCHASE.P')
                                    ->required()
                                    ->numeric()
                                    ->nullable(),
                                Forms\Components\TextInput::make('item_discount_percentage')
                                    ->label('DISCOUNT %')
                                    ->numeric()
                                    ->reactive()
                                    ->nullable(),
                                Forms\Components\TextInput::make('sub_total')
                                    ->label('SUB.TOTAL')
                                    ->numeric()
                                    ->reactive()
                                    ->nullable()
                                    ->readOnly(),
                            ])->columns(8),
                    ]),
                Forms\Components\Section::make('Summary')
                    ->schema([
                        Forms\Components\TextInput::make('gross_total')
                            ->label('Gross Total')
                            ->numeric()
                            ->reactive()
                            ->readOnly(),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Discount %')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $total_amount = $get('original_total_amount') ?? 0;
                                $discount_amount = ($total_amount * $state) / 100;
                                $set('discount_amount', $discount_amount);

                                $tax_percentage = $get('tax_percentage') ?? 0;
                                $total_after_discount = $total_amount - $discount_amount;
                                $tax_amount = ($total_after_discount * $tax_percentage) / 100;
                                $set('tax_amount', $tax_amount);

                                $total = $total_after_discount + $tax_amount;
                                $set('total', $total);
                            }),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->reactive()
                            ->readOnly(),
                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('Tax %')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $total_amount = $get('original_total_amount') ?? 0;
                                $discount_amount = $get('discount_amount') ?? 0;
                                $total_after_discount = $total_amount - $discount_amount;
                                $tax_amount = ($total_after_discount * $state) / 100;
                                $set('tax_amount', $tax_amount);

                                $total = $total_after_discount + $tax_amount;
                                $set('total', $total);
                            }),
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->reactive()
                            ->readOnly(),
                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->reactive()
                            ->readOnly(),
                    ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_invoice_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_amount')
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
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'view' => Pages\ViewPurchaseReturn::route('/{record}'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
