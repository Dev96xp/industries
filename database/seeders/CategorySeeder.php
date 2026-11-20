<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

/**
 * Safe to run any number of times: categories and subcategories are matched by slug,
 * so names and active switches changed in the admin are never overwritten. Only
 * missing rows are created and sort_order is reset to the order of the lists below.
 * The original product categories always come first, then the service categories.
 */
class CategorySeeder extends Seeder
{
    /**
     * Original product categories, used by ProductSeeder by name.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PRODUCT_CATEGORIES = [
        'materials' => ['Materials', 'Raw materials and supplies used in construction.'],
        'labor' => ['Labor', 'Labor costs including skilled and general workers.'],
        'equipment' => ['Equipment', 'Equipment rentals and machinery costs.'],
        'permits' => ['Permits', 'Government permits, inspections and fees.'],
        'other' => ['Other', 'Miscellaneous services and costs.'],
    ];

    /**
     * Service categories: slug => name [Spanish, English] and subcategories slug => [Spanish, English].
     *
     * @var array<string, array{name: array{0: string, 1: string}, subcategories: array<string, array{0: string, 1: string}>}>
     */
    public const CATEGORIES = [
        'construction' => [
            'name' => ['Construcción', 'Construction'],
            'subcategories' => [
                'general-contractor' => ['Contratista general', 'General Contractor'],
                'remodeling' => ['Remodelación', 'Remodeling'],
                'demolition' => ['Demolición', 'Demolition'],
                'excavation' => ['Excavación y preparación del terreno', 'Excavation & Site Work'],
                'foundation' => ['Cimentación', 'Foundation'],
                'concrete' => ['Concreto', 'Concrete'],
                'masonry' => ['Albañilería', 'Masonry'],
                'framing' => ['Estructura', 'Framing'],
                'roofing' => ['Techos', 'Roofing'],
                'siding' => ['Revestimiento exterior', 'Siding'],
                'windows-doors' => ['Ventanas y puertas', 'Windows & Doors'],
                'electrical' => ['Electricidad', 'Electrical'],
                'plumbing' => ['Plomería', 'Plumbing'],
                'hvac' => ['Calefacción y aire acondicionado', 'HVAC'],
                'insulation' => ['Aislamiento', 'Insulation'],
                'drywall' => ['Tablaroca', 'Drywall'],
                'painting' => ['Pintura', 'Painting'],
                'flooring' => ['Pisos', 'Flooring'],
                'tile' => ['Azulejo y cerámica', 'Tile'],
                'carpentry' => ['Carpintería y gabinetes', 'Carpentry & Cabinets'],
                'countertops' => ['Cubiertas', 'Countertops'],
                'landscaping' => ['Paisajismo', 'Landscaping'],
                'fencing' => ['Cercas', 'Fencing'],
                'decks-patios' => ['Terrazas y patios', 'Decks & Patios'],
                'gutters' => ['Canaletas', 'Gutters'],
                'paving' => ['Pavimentación', 'Paving'],
                'construction-cleaning' => ['Limpieza de obra', 'Construction Cleanup'],
                'handyman' => ['Reparaciones menores', 'Handyman'],
            ],
        ],
        'maintenance' => [
            'name' => ['Mantenimiento y reparaciones', 'Maintenance & Repair'],
            'subcategories' => [
                'plumbing-repair' => ['Reparación de plomería', 'Plumbing Repair'],
                'electrical-repair' => ['Reparación eléctrica', 'Electrical Repair'],
                'hvac-maintenance' => ['Mantenimiento de A/C y calefacción', 'HVAC Maintenance'],
                'appliance-repair' => ['Reparación de electrodomésticos', 'Appliance Repair'],
                'locksmith' => ['Cerrajería', 'Locksmith'],
            ],
        ],
        'home-cleaning' => [
            'name' => ['Hogar y limpieza', 'Home & Cleaning'],
            'subcategories' => [
                'house-cleaning' => ['Limpieza de casas', 'House Cleaning'],
                'gardening' => ['Jardinería', 'Gardening'],
                'pest-control' => ['Control de plagas', 'Pest Control'],
                'pool-cleaning' => ['Limpieza de albercas', 'Pool Cleaning'],
                'carpet-cleaning' => ['Lavado de alfombras', 'Carpet Cleaning'],
            ],
        ],
        'auto' => [
            'name' => ['Mecánica automotriz', 'Auto Services'],
            'subcategories' => [
                'auto-mechanic' => ['Mecánica general', 'Auto Mechanic'],
                'auto-body' => ['Hojalatería y pintura', 'Auto Body & Paint'],
                'tires' => ['Llantas', 'Tires'],
                'car-detailing' => ['Lavado y detallado', 'Car Detailing'],
                'towing' => ['Grúas', 'Towing'],
            ],
        ],
        'food' => [
            'name' => ['Comida y bebidas', 'Food & Beverage'],
            'subcategories' => [
                'restaurants' => ['Restaurantes', 'Restaurants'],
                'catering' => ['Banquetes', 'Catering'],
                'bakery' => ['Repostería', 'Bakery'],
                'food-trucks' => ['Food trucks', 'Food Trucks'],
                'personal-chef' => ['Chef a domicilio', 'Personal Chef'],
            ],
        ],
        'events' => [
            'name' => ['Eventos especiales', 'Events'],
            'subcategories' => [
                'event-photography' => ['Fotografía y video', 'Photography & Video'],
                'dj-music' => ['DJ y música', 'DJ & Music'],
                'event-decoration' => ['Decoración', 'Decoration'],
                'party-rentals' => ['Renta de mobiliario', 'Party Rentals'],
                'event-planning' => ['Organización de eventos', 'Event Planning'],
            ],
        ],
        'real-estate' => [
            'name' => ['Bienes raíces', 'Real Estate'],
            'subcategories' => [
                'home-sales' => ['Venta de propiedades', 'Home Sales'],
                'rentals' => ['Renta de propiedades', 'Rentals'],
                'property-management' => ['Administración de propiedades', 'Property Management'],
                'appraisals' => ['Avalúos', 'Appraisals'],
                'home-inspection' => ['Inspección de casas', 'Home Inspection'],
            ],
        ],
        'beauty' => [
            'name' => ['Belleza y cuidado personal', 'Beauty & Personal Care'],
            'subcategories' => [
                'barbershop' => ['Barbería', 'Barbershop'],
                'hair-salon' => ['Estética', 'Hair Salon'],
                'nails' => ['Uñas', 'Nails'],
                'makeup' => ['Maquillaje', 'Makeup'],
                'spa' => ['Spa', 'Spa'],
            ],
        ],
        'health' => [
            'name' => ['Salud y bienestar', 'Health & Wellness'],
            'subcategories' => [
                'fitness-training' => ['Entrenamiento personal', 'Personal Training'],
                'nutrition' => ['Nutrición', 'Nutrition'],
                'therapy' => ['Terapia', 'Therapy'],
                'massage' => ['Masajes', 'Massage'],
                'yoga' => ['Yoga y meditación', 'Yoga & Meditation'],
            ],
        ],
        'education' => [
            'name' => ['Educación y clases', 'Education & Tutoring'],
            'subcategories' => [
                'tutoring' => ['Tutorías', 'Tutoring'],
                'languages' => ['Idiomas', 'Languages'],
                'music-lessons' => ['Clases de música', 'Music Lessons'],
                'test-prep' => ['Preparación de exámenes', 'Test Prep'],
                'online-courses' => ['Cursos en línea', 'Online Courses'],
            ],
        ],
        'technology' => [
            'name' => ['Tecnología', 'Technology'],
            'subcategories' => [
                'computer-repair' => ['Reparación de computadoras', 'Computer Repair'],
                'web-development' => ['Desarrollo web', 'Web Development'],
                'networking' => ['Redes e internet', 'Networking'],
                'robotics' => ['Robótica', 'Robotics'],
                'phone-repair' => ['Reparación de celulares', 'Phone Repair'],
            ],
        ],
        'science' => [
            'name' => ['Ciencia', 'Science'],
            'subcategories' => [
                'biology' => ['Biología', 'Biology'],
                'chemistry' => ['Química', 'Chemistry'],
                'lab-services' => ['Servicios de laboratorio', 'Lab Services'],
                'environmental-consulting' => ['Consultoría ambiental', 'Environmental Consulting'],
                'research' => ['Investigación', 'Research'],
            ],
        ],
        'art' => [
            'name' => ['Arte y diseño', 'Art & Design'],
            'subcategories' => [
                'fine-art' => ['Pintura artística', 'Fine Art'],
                'graphic-design' => ['Diseño gráfico', 'Graphic Design'],
                'tattoos' => ['Tatuajes', 'Tattoos'],
                'crafts' => ['Artesanías', 'Crafts'],
                'murals' => ['Murales', 'Murals'],
            ],
        ],
        'business' => [
            'name' => ['Negocios y profesionales', 'Business & Professional'],
            'subcategories' => [
                'accounting' => ['Contabilidad', 'Accounting'],
                'legal' => ['Servicios legales', 'Legal Services'],
                'taxes' => ['Impuestos', 'Taxes'],
                'marketing' => ['Marketing', 'Marketing'],
                'insurance' => ['Seguros', 'Insurance'],
            ],
        ],
        'pets' => [
            'name' => ['Mascotas', 'Pets'],
            'subcategories' => [
                'veterinary' => ['Veterinaria', 'Veterinary'],
                'pet-grooming' => ['Estética canina', 'Pet Grooming'],
                'dog-training' => ['Entrenamiento', 'Dog Training'],
                'dog-walking' => ['Paseo de perros', 'Dog Walking'],
                'pet-sitting' => ['Cuidado de mascotas', 'Pet Sitting'],
            ],
        ],
        'transport' => [
            'name' => ['Transporte y logística', 'Transport & Logistics'],
            'subcategories' => [
                'moving' => ['Mudanzas', 'Moving'],
                'delivery' => ['Entregas y mensajería', 'Delivery'],
                'freight' => ['Carga y fletes', 'Freight'],
                'junk-removal' => ['Retiro de escombro y basura', 'Junk Removal'],
                'chauffeur' => ['Chofer privado', 'Chauffeur'],
            ],
        ],
        'care' => [
            'name' => ['Cuidado de niños y adultos mayores', 'Child & Senior Care'],
            'subcategories' => [
                'childcare' => ['Guardería', 'Childcare'],
                'babysitting' => ['Niñera', 'Babysitting'],
                'senior-care' => ['Cuidado de adultos mayores', 'Senior Care'],
                'home-health-aide' => ['Enfermería a domicilio', 'Home Health Aide'],
                'special-needs-care' => ['Cuidado de necesidades especiales', 'Special Needs Care'],
            ],
        ],
        'fashion' => [
            'name' => ['Moda y costura', 'Fashion & Tailoring'],
            'subcategories' => [
                'tailoring' => ['Sastrería y arreglos', 'Tailoring & Alterations'],
                'custom-clothing' => ['Ropa a la medida', 'Custom Clothing'],
                'embroidery' => ['Bordado y estampado', 'Embroidery & Printing'],
                'shoe-repair' => ['Reparación de calzado', 'Shoe Repair'],
                'fashion-design' => ['Diseño de modas', 'Fashion Design'],
            ],
        ],
        'sports' => [
            'name' => ['Deportes y recreación', 'Sports & Recreation'],
            'subcategories' => [
                'sports-coaching' => ['Entrenador deportivo', 'Sports Coaching'],
                'martial-arts' => ['Artes marciales', 'Martial Arts'],
                'swimming-lessons' => ['Clases de natación', 'Swimming Lessons'],
                'sports-leagues' => ['Ligas y torneos', 'Leagues & Tournaments'],
                'outdoor-adventures' => ['Aventura al aire libre', 'Outdoor Adventures'],
            ],
        ],
        'agriculture' => [
            'name' => ['Agricultura y ganadería', 'Agriculture & Livestock'],
            'subcategories' => [
                'farming' => ['Cultivos', 'Farming'],
                'livestock' => ['Ganadería', 'Livestock'],
                'irrigation' => ['Riego', 'Irrigation'],
                'farm-equipment' => ['Maquinaria agrícola', 'Farm Equipment'],
                'produce-sales' => ['Venta de productos del campo', 'Farm Produce'],
            ],
        ],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::PRODUCT_CATEGORIES as $slug => [$name, $description]) {
            $category = Category::where('slug', $slug)->first()
                ?? Category::where('name', $name)->first()
                ?? Category::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => true,
                ]);

            $category->update(['sort_order' => $position++]);
        }

        foreach (self::CATEGORIES as $slug => $definition) {
            [$name, $nameEn] = $definition['name'];

            $category = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'name_en' => $nameEn, 'is_active' => true],
            );

            $category->update(['sort_order' => $position++]);

            $subPosition = 0;

            foreach ($definition['subcategories'] as $subSlug => [$subName, $subNameEn]) {
                $subcategory = Subcategory::firstOrCreate(
                    ['slug' => $subSlug],
                    ['category_id' => $category->id, 'name' => $subName, 'name_en' => $subNameEn, 'is_active' => true],
                );

                $subcategory->update(['sort_order' => $subPosition++]);
            }
        }
    }
}
