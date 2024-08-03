<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Closure;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ExportAction as ActionsExportAction;
use Filament\Tables\Actions\ImportAction as ActionsImportAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    public static function getNavigationBadge(): ?string
   {
       return static::getModel()::count();
   }
    protected static int $globalSearchResultsLimit = 20;

    protected static ?int $navigationSort = -1; 
    protected static ?string $navigationIcon = 'heroicon-s-shopping-bag';
    protected static ?string $navigationGroup = 'Item Setup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Image')
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->directory('images/products')
                            ->imageEditor()
                            ->rules([
                                'nullable',
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('image') && $get('image_url')) {
                                        $fail('Please provide either an image file or an image URL, not both.');
                                    }
                                }
                            ]),
                        TextInput::make('image_url')
                            ->url()
                            ->label('Image URL')
                            ->placeholder('https://example.com/image.jpg')
                            ->rules([
                                'nullable',
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('image') && $get('image_url')) {
                                        $fail('Please provide either an image file or an image URL, not both.');
                                    }
                                }
                            ]),
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
                    ->autofocus()
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
                    Forms\Components\TextInput::make('margin')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('max_discount')
                    ->numeric()
                    ->readOnly(),
                ])->columns(8),

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
                Tables\Columns\TextColumn::make('id')->sortable(),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    
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
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->label('Brand'),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->label('Category'),
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('update')
                    ->label('Update')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('warning')
                    ->action(fn (Collection $records, array $data) => ListProducts::bulkUpdate($records, $data))
                        ->form([
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
                        ])
                        ->requiresConfirmation()
                ])
            ])
            ->headerActions([
                ActionsImportAction::make()
                    ->importer(ProductImporter::class)
                    ->label('Import Products'),
                ActionsExportAction::make()
                    ->exporter(ProductExporter::class)
                    ->label('Export Products'),
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

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {
        $imageUrl = '';
        if ($record->image) {
            if (filter_var($record->image, FILTER_VALIDATE_URL)) {
                $imageUrl = $record->image;
            } else {
                $imageUrl = asset('storage/' . $record->image);
            }
        } else {
            // Use a default image path if needed, e.g., a placeholder image
            $imageUrl = asset('images/no-image.png'); // Change this to your default image path
        }

        return new HtmlString('<img src="' . $imageUrl . '" alt="' . $record->name . '" style="width: 150px; height: 120px; object-fit: cover; border-radius: 5%;"> ' . $record->name);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'formulation'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Name' => $record->name,
            'Formulation' => $record->formulation,
            'Brand' => $record->brand->name,
            'Supplier' => $record->supplier->name,
            'Category' => $record->category->name,
            'Pack Size' => $record->pack_size,
            'Sale Price' => $record->pack_sale_price,
            'Purchase Price' => $record->pack_purchase_price,
            'Quantity' => $record->quantity,

        ];
    }
}
