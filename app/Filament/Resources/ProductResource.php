<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static int $globalSearchResultsLimit = 20;

    protected static ?int $navigationSort = -1;
    protected static ?string $navigationIcon = 'heroicon-s-shopping-bag';
    protected static ?string $navigationGroup = 'Item Setup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\FileUpload::make('image')
                    ->image()
                    ->directory('images/products')
                    ->imageEditor(),
                ]),
                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('product_code')
                    ->required()
                    ->maxLength(255)
                    ->readOnly()
                    ->default(Product::generateCode()),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    Forms\Components\TextInput::make('formulation')
                    ->maxLength(255),
                ])->columns(3),
                Forms\Components\Section::make()
                ->schema([
                Forms\Components\TextInput::make('description')
                ->columnSpan(2),
                Forms\Components\TextInput::make('pack_size')
                    ->required()
                    ->numeric(),
                ])->columns(3),
                
                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
                ])->columns(3),

                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('pack_purchase_price')
                    ->label('Pack P.Price')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('pack_sale_price')
                    ->label('Pack S.Price')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('unit_purchase_price')
                    ->label('Unit P.Price')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('unit_sale_price')
                    ->label('Unit S.Price')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('avg_price')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('max_discount')
                    ->numeric()
                    ->readOnly(),
                ])->columns(7),

                Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Toggle::make('narcotic'),
                ]),
                

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('formulation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pack_size')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pack_purchase_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pack_sale_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_purchase_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_sale_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('avg_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('narcotic')
                    ->boolean(),
                Tables\Columns\TextColumn::make('max_discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand_id')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
