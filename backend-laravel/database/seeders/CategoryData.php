<?php

namespace Database\Seeders;

class CategoryData
{
    public static function get(): array
    {
        return [
            [
                'name' => 'Smartphones & Mobile Tech',
                'slug' => 'smartphones-mobile-tech',
                'description' => 'Flagship, mid-range and budget smartphones with the latest mobile technology and accessories.',
                'subcategories' => [
                    ['name' => 'Flagship Smartphones', 'slug' => 'flagship-smartphones', 'description' => 'Top-tier flagship devices featuring premium hardware and cameras.'],
                    ['name' => 'Mid-Range Smartphones', 'slug' => 'mid-range-smartphones', 'description' => 'Great performance and camera systems with balanced pricing.'],
                    ['name' => 'Budget Smartphones', 'slug' => 'budget-smartphones', 'description' => 'Affordable mobile phones ideal for daily essentials and long battery life.'],
                    ['name' => 'Feature Phones', 'slug' => 'feature-phones', 'description' => 'Durable keypad phones with multi-day battery endurance.'],
                ]
            ],
            [
                'name' => 'Laptops & Ultrabooks',
                'slug' => 'laptops-ultrabooks',
                'description' => 'High performance laptops, thin ultrabooks, gaming powerhouses and productivity machines.',
                'subcategories' => [
                    ['name' => 'Gaming Laptops', 'slug' => 'gaming-laptops', 'description' => 'Dedicated graphics and high-refresh displays for serious gamers.'],
                    ['name' => 'Thin & Light Ultrabooks', 'slug' => 'thin-light-ultrabooks', 'description' => 'Ultra-portable laptops for creators and remote professionals.'],
                    ['name' => 'Business Laptops', 'slug' => 'business-laptops', 'description' => 'Reliable, secure workstations built for corporate tasks.'],
                    ['name' => '2-in-1 Convertibles', 'slug' => '2-in-1-convertibles', 'description' => 'Versatile touchscreen laptops with flexible 360-degree hinges.'],
                ]
            ],
            [
                'name' => 'Desktop Computers',
                'slug' => 'desktop-computers',
                'description' => 'Pre-built desktops, compact mini PCs, all-in-one machines, and creator workstations.',
                'subcategories' => [
                    ['name' => 'Custom Gaming PCs', 'slug' => 'custom-gaming-pcs', 'description' => 'Liquid-cooled gaming towers optimized for high FPS gameplay.'],
                    ['name' => 'All-in-One PCs', 'slug' => 'all-in-one-pcs', 'description' => 'Sleek desktop systems with integrated high-resolution displays.'],
                    ['name' => 'Workstations', 'slug' => 'workstations', 'description' => 'Heavy-duty rendering and CAD workstations with multi-core power.'],
                    ['name' => 'Mini PCs', 'slug' => 'mini-pcs', 'description' => 'Compact form factor PCs for home theater and desk setups.'],
                ]
            ],
            [
                'name' => 'Computer Components',
                'slug' => 'computer-components',
                'description' => 'Core PC hardware including CPUs, GPUs, motherboards, RAM, and power supplies.',
                'subcategories' => [
                    ['name' => 'Processors & CPUs', 'slug' => 'processors-cpus', 'description' => 'Cutting edge multi-core processors from Intel and AMD.'],
                    ['name' => 'Graphics Cards', 'slug' => 'graphics-cards', 'description' => 'Dedicated NVIDIA and AMD graphics cards for gaming and AI.'],
                    ['name' => 'Motherboards', 'slug' => 'motherboards', 'description' => 'ATX and mini-ITX motherboards supporting latest chipset standards.'],
                    ['name' => 'RAM & Memory', 'slug' => 'ram-memory', 'description' => 'High-speed DDR4 and DDR5 memory modules.'],
                ]
            ],
            [
                'name' => 'Computer Storage',
                'slug' => 'computer-storage',
                'description' => 'Blazing fast solid state drives, external storage, and high-capacity hard drives.',
                'subcategories' => [
                    ['name' => 'NVMe M.2 SSDs', 'slug' => 'nvme-m2-ssds', 'description' => 'Ultra high speed PCIe 4.0 and 5.0 internal solid state drives.'],
                    ['name' => 'External Hard Drives', 'slug' => 'external-hard-drives', 'description' => 'Portable USB 3.2 drives for backups and large files.'],
                    ['name' => 'SATA SSDs', 'slug' => 'sata-ssds', 'description' => 'Standard 2.5-inch solid state drives for storage expansion.'],
                    ['name' => 'USB Flash Drives', 'slug' => 'usb-flash-drives', 'description' => 'Handy thumb drives with fast read/write speeds.'],
                ]
            ],
            [
                'name' => 'Monitors & Displays',
                'slug' => 'monitors-displays',
                'description' => 'Vibrant computer monitors for esports gaming, graphic design, and office productivity.',
                'subcategories' => [
                    ['name' => 'Gaming Monitors', 'slug' => 'gaming-monitors', 'description' => '144Hz to 240Hz monitors with 1ms response time and FreeSync.'],
                    ['name' => '4K Professional Displays', 'slug' => '4k-professional-displays', 'description' => 'Color-accurate IPS panels engineered for photo and video editing.'],
                    ['name' => 'Curved Monitors', 'slug' => 'curved-monitors', 'description' => 'Immersive ultrawide curved panels for seamless multitasking.'],
                    ['name' => 'Portable Displays', 'slug' => 'portable-displays', 'description' => 'Lightweight USB-C external monitors for work on the go.'],
                ]
            ],
            [
                'name' => 'PC Peripherals & Accessories',
                'slug' => 'pc-peripherals-accessories',
                'description' => 'Mice, mechanical keyboards, desk pads, webcams, and docking stations.',
                'subcategories' => [
                    ['name' => 'Mechanical Keyboards', 'slug' => 'mechanical-keyboards', 'description' => 'Tactile and linear switches with customizable RGB backlighting.'],
                    ['name' => 'Gaming & Wireless Mice', 'slug' => 'gaming-wireless-mice', 'description' => 'Ergonomic lightweight mice with high DPI optical sensors.'],
                    ['name' => 'Desk Pads & Mousepads', 'slug' => 'desk-pads-mousepads', 'description' => 'Smooth micro-weave cloth pads for glide precision.'],
                    ['name' => 'Webcams & Microphones', 'slug' => 'webcams-microphones', 'description' => 'Crystal clear 1080p and 4K streaming accessories.'],
                ]
            ],
            [
                'name' => 'Networking & WiFi',
                'slug' => 'networking-wifi',
                'description' => 'Fast and reliable networking hardware including routers, extenders, and mesh systems.',
                'subcategories' => [
                    ['name' => 'WiFi 6 Routers', 'slug' => 'wifi-6-routers', 'description' => 'Dual and tri-band routers providing gigabit wireless speeds.'],
                    ['name' => 'Mesh WiFi Systems', 'slug' => 'mesh-wifi-systems', 'description' => 'Whole-home seamless roaming coverage without dead zones.'],
                    ['name' => 'Range Extenders', 'slug' => 'range-extenders', 'description' => 'Plug-and-play signal boosters for stubborn wireless dead spots.'],
                    ['name' => 'Network Switches', 'slug' => 'network-switches', 'description' => 'Gigabit Ethernet switches for smart homes and offices.'],
                ]
            ],
            [
                'name' => 'Tablets & E-Readers',
                'slug' => 'tablets-e-readers',
                'description' => 'Touchscreen tablets for entertainment, graphic design, reading, and notes.',
                'subcategories' => [
                    ['name' => 'Apple iPads', 'slug' => 'apple-ipads', 'description' => 'iPad Air, iPad Pro, and Mini powered by Apple Silicon.'],
                    ['name' => 'Android Tablets', 'slug' => 'android-tablets', 'description' => 'Versatile Android tablets with AMOLED displays and stylus support.'],
                    ['name' => 'Graphic Drawing Tablets', 'slug' => 'graphic-drawing-tablets', 'description' => 'Pressure-sensitive pen displays for digital artists.'],
                    ['name' => 'E-Ink Readers', 'slug' => 'e-ink-readers', 'description' => 'Glare-free digital readers with paper-like book displays.'],
                ]
            ],
            [
                'name' => 'Smartwatches & Wearables',
                'slug' => 'smartwatches-wearables',
                'description' => 'Wristwear with health tracking, GPS navigation, and smartphone notifications.',
                'subcategories' => [
                    ['name' => 'Fitness Trackers', 'slug' => 'fitness-trackers', 'description' => 'Lightweight activity bands tracking heart rate, sleep, and steps.'],
                    ['name' => 'Premium Smartwatches', 'slug' => 'premium-smartwatches', 'description' => 'Full-featured smartwatches with AMOLED touchscreens and ECG.'],
                    ['name' => 'Sports GPS Watches', 'slug' => 'sports-gps-watches', 'description' => 'Rugged multisport watches with offline topo maps.'],
                    ['name' => 'Smart Rings', 'slug' => 'smart-rings', 'description' => 'Discreet titanium rings tracking recovery and sleep metrics.'],
                ]
            ],
            [
                'name' => 'Headphones & Audio',
                'slug' => 'headphones-audio',
                'description' => 'Wireless earbuds, studio monitor cans, and noise cancelling audio gear.',
                'subcategories' => [
                    ['name' => 'True Wireless Earbuds', 'slug' => 'true-wireless-earbuds', 'description' => 'Bluetooth earbuds with active noise cancellation and wireless charging.'],
                    ['name' => 'Over-Ear ANC Headphones', 'slug' => 'over-ear-anc-headphones', 'description' => 'Plush memory foam headphones blocking ambient travel noise.'],
                    ['name' => 'Gaming Headsets', 'slug' => 'gaming-headsets', 'description' => 'Surround sound spatial headsets with noise-canceling mics.'],
                    ['name' => 'In-Ear Studio Monitors', 'slug' => 'in-ear-studio-monitors', 'description' => 'Balanced armature wired earphones for audiophiles.'],
                ]
            ],
            [
                'name' => 'Speakers & Home Audio',
                'slug' => 'speakers-home-audio',
                'description' => 'Portable bluetooth speakers, immersive TV soundbars, and party audio systems.',
                'subcategories' => [
                    ['name' => 'Portable Bluetooth Speakers', 'slug' => 'portable-bluetooth-speakers', 'description' => 'Rugged waterproof speakers for outdoor adventures.'],
                    ['name' => 'TV Soundbars', 'slug' => 'tv-soundbars', 'description' => 'Dolby Atmos soundbars with wireless subwoofers.'],
                    ['name' => 'Home Theater Systems', 'slug' => 'home-theater-systems', 'description' => 'Multi-channel surround speaker setups for movie nights.'],
                    ['name' => 'Party Speakers', 'slug' => 'party-speakers', 'description' => 'High-wattage speakers with dynamic strobe lighting.'],
                ]
            ],
            [
                'name' => 'Cameras & Photography',
                'slug' => 'cameras-photography',
                'description' => 'Capture every moment with mirrorless cameras, action cams, and premium lenses.',
                'subcategories' => [
                    ['name' => 'Mirrorless Cameras', 'slug' => 'mirrorless-cameras', 'description' => 'Compact full-frame and APS-C mirrorless cameras.'],
                    ['name' => 'Action & 360 Cameras', 'slug' => 'action-360-cameras', 'description' => 'Waterproof action cameras with stabilization for sports.'],
                    ['name' => 'Camera Lenses', 'slug' => 'camera-lenses', 'description' => 'Prime and telephoto lenses with wide apertures.'],
                    ['name' => 'Gimbals & Tripods', 'slug' => 'gimbals-tripods', 'description' => 'Handheld 3-axis stabilizers and carbon fiber tripods.'],
                ]
            ],
            [
                'name' => 'Smart Home & IoT',
                'slug' => 'smart-home-iot',
                'description' => 'Intelligent lighting, security cameras, smart locks, and connected automation.',
                'subcategories' => [
                    ['name' => 'Smart Bulbs & Lighting', 'slug' => 'smart-bulbs-lighting', 'description' => 'RGB and tunable white smart bulbs controlled via app.'],
                    ['name' => 'Smart Security Cameras', 'slug' => 'smart-security-cameras', 'description' => 'WiFi indoor and outdoor cameras with night vision.'],
                    ['name' => 'Smart Plugs & Switches', 'slug' => 'smart-plugs-switches', 'description' => 'App-controlled power outlets with energy monitoring.'],
                    ['name' => 'Smart Door Locks', 'slug' => 'smart-door-locks', 'description' => 'Keyless entry locks with fingerprint and passcode access.'],
                ]
            ],
            [
                'name' => 'Televisions & Video',
                'slug' => 'televisions-video',
                'description' => 'Stunning 4K OLED, QLED TVs, digital projectors, and streaming gadgets.',
                'subcategories' => [
                    ['name' => '4K OLED & QLED TVs', 'slug' => '4k-oled-qled-tvs', 'description' => 'Deep blacks, vibrant colors, and HDR10+ support.'],
                    ['name' => 'Smart Android TVs', 'slug' => 'smart-android-tvs', 'description' => 'Built-in Google TV, voice search, and streaming apps.'],
                    ['name' => 'Home Theater Projectors', 'slug' => 'home-theater-projectors', 'description' => 'Laser and LED projectors for cinematic 120-inch displays.'],
                    ['name' => 'Streaming Media Players', 'slug' => 'streaming-media-players', 'description' => 'Fast 4K streaming sticks and TV boxes.'],
                ]
            ],
            [
                'name' => 'Gaming Consoles & Accessories',
                'slug' => 'gaming-consoles-accessories',
                'description' => 'Next-gen gaming consoles, gamepads, racing wheels, and virtual reality gear.',
                'subcategories' => [
                    ['name' => 'PlayStation Consoles & Games', 'slug' => 'playstation-consoles-games', 'description' => 'Sony PS5 hardware, accessories, and disc titles.'],
                    ['name' => 'Xbox Consoles & Accessories', 'slug' => 'xbox-consoles-accessories', 'description' => 'Xbox Series X/S consoles and wireless controllers.'],
                    ['name' => 'Nintendo Switch Systems', 'slug' => 'nintendo-switch-systems', 'description' => 'Portable OLED consoles and Joy-Con gamepads.'],
                    ['name' => 'Game Controllers & Wheels', 'slug' => 'game-controllers-wheels', 'description' => 'Custom pro wireless gamepads and force-feedback steering wheels.'],
                ]
            ],
            [
                'name' => 'Men\'s Formal Wear',
                'slug' => 'mens-formal-wear',
                'description' => 'Tailored suits, crisp formal shirts, dress trousers, and executive accessories.',
                'subcategories' => [
                    ['name' => 'Men\'s Suits & Blazers', 'slug' => 'mens-suits-blazers', 'description' => 'Slim and tailored blazers crafted for corporate and formal events.'],
                    ['name' => 'Men\'s Formal Shirts', 'slug' => 'mens-formal-shirts', 'description' => '100% Egyptian cotton crisp shirts in timeless patterns.'],
                    ['name' => 'Men\'s Dress Trousers', 'slug' => 'mens-dress-trousers', 'description' => 'Comfort stretch formal trousers with clean pleats.'],
                    ['name' => 'Men\'s Ties & Cufflinks', 'slug' => 'mens-ties-cufflinks', 'description' => 'Pure silk neckties, pocket squares, and metallic cufflinks.'],
                ]
            ],
            [
                'name' => 'Men\'s Casual Wear',
                'slug' => 'mens-casual-wear',
                'description' => 'Everyday comfort clothing including t-shirts, polos, denim jeans, and hoodies.',
                'subcategories' => [
                    ['name' => 'Men\'s Graphic T-Shirts', 'slug' => 'mens-graphic-t-shirts', 'description' => 'Soft combed cotton printed crewneck tees.'],
                    ['name' => 'Men\'s Polo Shirts', 'slug' => 'mens-polo-shirts', 'description' => 'Classic pique cotton polo shirts with ribbed collars.'],
                    ['name' => 'Men\'s Denim Jeans', 'slug' => 'mens-denim-jeans', 'description' => 'Durable stretch denim in slim, straight, and relaxed fits.'],
                    ['name' => 'Men\'s Hoodies & Sweatshirts', 'slug' => 'mens-hoodies-sweatshirts', 'description' => 'Fleece-lined pullover hoodies and casual crewneck sweatshirts.'],
                ]
            ],
            [
                'name' => 'Men\'s Traditional Wear',
                'slug' => 'mens-traditional-wear',
                'description' => 'Authentic ethnic attire for festivals, weddings, and traditional occasions.',
                'subcategories' => [
                    ['name' => 'Men\'s Premium Panjabi', 'slug' => 'mens-premium-panjabi', 'description' => 'Hand-embroidered jacquard and cotton panjabi for Eid and weddings.'],
                    ['name' => 'Men\'s Kurta Pajama', 'slug' => 'mens-kurta-pajama', 'description' => 'Comfortable linen and silk kurta sets for festive celebrations.'],
                    ['name' => 'Men\'s Traditional Lungi', 'slug' => 'mens-traditional-lungi', 'description' => 'Handloom fine cotton lungi with comfortable weave.'],
                    ['name' => 'Men\'s Koti & Waistcoats', 'slug' => 'mens-koti-waistcoats', 'description' => 'Structured Nehru jackets and embroidered ethnic waistcoats.'],
                ]
            ],
            [
                'name' => 'Women\'s Traditional Wear',
                'slug' => 'womens-traditional-wear',
                'description' => 'Exquisite traditional sarees, salwar suits, kurtis, and festive bridal ensembles.',
                'subcategories' => [
                    ['name' => 'Jamdani & Silk Sarees', 'slug' => 'jamdani-silk-sarees', 'description' => 'Dhakai Jamdani, pure Katan, and silk woven sarees.'],
                    ['name' => 'Designer Salwar Kameez', 'slug' => 'designer-salwar-kameez', 'description' => 'Three-piece embroidered georgette and lawn suits.'],
                    ['name' => 'Embroidered Kurtis & Tunics', 'slug' => 'embroidered-kurtis-tunics', 'description' => 'Contemporary ethnic kurtis for university and office wear.'],
                    ['name' => 'Bridal & Festive Lehengas', 'slug' => 'bridal-festive-lehengas', 'description' => 'Lavish velvet and organza lehenga sets with intricate zari work.'],
                ]
            ],
            [
                'name' => 'Women\'s Western Wear',
                'slug' => 'womens-western-wear',
                'description' => 'Modern dresses, blouses, tops, chic skirts, and denim trousers for women.',
                'subcategories' => [
                    ['name' => 'Women\'s Dresses & Jumpsuits', 'slug' => 'womens-dresses-jumpsuits', 'description' => 'Flattering floral midi dresses, wraps, and evening gowns.'],
                    ['name' => 'Women\'s Tops & Blouses', 'slug' => 'womens-tops-blouses', 'description' => 'Satin, chiffon, and cotton casual and formal tops.'],
                    ['name' => 'Women\'s Denim & Jeans', 'slug' => 'womens-denim-jeans', 'description' => 'High-waisted, mom-fit, and flared stretch denim pants.'],
                    ['name' => 'Women\'s Blazers & Jackets', 'slug' => 'womens-blazers-jackets', 'description' => 'Structured work blazers and cropped casual jackets.'],
                ]
            ],
            [
                'name' => 'Women\'s Activewear & Lounge',
                'slug' => 'womens-activewear-lounge',
                'description' => 'Performance gym wear, sports bras, seamless leggings, and cozy loungewear.',
                'subcategories' => [
                    ['name' => 'Yoga Pants & Leggings', 'slug' => 'yoga-pants-leggings', 'description' => 'Squat-proof four-way stretch compression leggings.'],
                    ['name' => 'High-Impact Sports Bras', 'slug' => 'high-impact-sports-bras', 'description' => 'Supportive moisture-wicking bras for workouts and running.'],
                    ['name' => 'Cozy Loungewear Sets', 'slug' => 'cozy-loungewear-sets', 'description' => 'Plush matching sweatpants and hoodie co-ord sets.'],
                    ['name' => 'Sleepwear & Pajamas', 'slug' => 'sleepwear-pajamas', 'description' => 'Breathable modal and cotton pajama sets for deep sleep.'],
                ]
            ],
            [
                'name' => 'Men\'s Footwear',
                'slug' => 'mens-footwear',
                'description' => 'Sneakers, leather formal dress shoes, casual loafers, and slides.',
                'subcategories' => [
                    ['name' => 'Men\'s Running Sneakers', 'slug' => 'mens-running-sneakers', 'description' => 'Cushioned athletic sneakers for jogging and road workouts.'],
                    ['name' => 'Men\'s Leather Loafers', 'slug' => 'mens-leather-loafers', 'description' => 'Handcrafted slip-on leather penny loafers.'],
                    ['name' => 'Men\'s Oxford Shoes', 'slug' => 'mens-oxford-shoes', 'description' => 'Polished genuine leather dress shoes for formal suits.'],
                    ['name' => 'Men\'s Casual Sandals', 'slug' => 'mens-casual-sandals', 'description' => 'Comfortable everyday leather strap sandals and slides.'],
                ]
            ],
            [
                'name' => 'Women\'s Footwear',
                'slug' => 'womens-footwear',
                'description' => 'High heels, stylish flats, wedges, sneakers, and ankle boots.',
                'subcategories' => [
                    ['name' => 'Women\'s High Heels & Pumps', 'slug' => 'womens-high-heels-pumps', 'description' => 'Classic pointed toe stilettos and block heel pumps.'],
                    ['name' => 'Women\'s Ballet Flats', 'slug' => 'womens-ballet-flats', 'description' => 'Cushioned daily walking slip-on flats.'],
                    ['name' => 'Women\'s Fashion Sneakers', 'slug' => 'womens-fashion-sneakers', 'description' => 'Platform casual sneakers for modern street style.'],
                    ['name' => 'Women\'s Ankle Boots', 'slug' => 'womens-ankle-boots', 'description' => 'Chic leather and suede zipped booties.'],
                ]
            ],
            [
                'name' => 'Watches & Horology',
                'slug' => 'watches-horology',
                'description' => 'Analog timepieces, luxury chronographs, automatic movements, and digital watches.',
                'subcategories' => [
                    ['name' => 'Automatic Mechanical Watches', 'slug' => 'automatic-mechanical-watches', 'description' => 'Self-winding wristwatches with exhibition casebacks.'],
                    ['name' => 'Chronograph Watches', 'slug' => 'chronograph-watches', 'description' => 'Multi-dial stopwatch function timepieces with tachymeter.'],
                    ['name' => 'Minimalist Quartz Watches', 'slug' => 'minimalist-quartz-watches', 'description' => 'Ultra-thin dress watches with clean dials.'],
                    ['name' => 'Sports Digital Watches', 'slug' => 'sports-digital-watches', 'description' => 'Shock-resistant and waterproof utility sports watches.'],
                ]
            ],
            [
                'name' => 'Bags & Luggage',
                'slug' => 'bags-luggage',
                'description' => 'Suitcases, laptop bags, leather backpacks, and weekend travel duffles.',
                'subcategories' => [
                    ['name' => 'Travel Hard-Shell Luggage', 'slug' => 'travel-hard-shell-luggage', 'description' => 'Polycarbonate rolling spinner suitcases with TSA locks.'],
                    ['name' => 'Everyday Laptop Backpacks', 'slug' => 'everyday-laptop-backpacks', 'description' => 'Waterproof padded backpacks with USB charging ports.'],
                    ['name' => 'Leather Messenger Bags', 'slug' => 'leather-messenger-bags', 'description' => 'Classic briefcase cross-body bags for executives.'],
                    ['name' => 'Gym & Weekend Duffles', 'slug' => 'gym-weekend-duffles', 'description' => 'Spacious carry-on duffle bags with shoe compartments.'],
                ]
            ],
            [
                'name' => 'Eyewear & Sunglasses',
                'slug' => 'eyewear-sunglasses',
                'description' => 'UV400 polarized shades, designer frames, and computer blue light glasses.',
                'subcategories' => [
                    ['name' => 'Polarized Sunglasses', 'slug' => 'polarized-sunglasses', 'description' => 'Anti-glare lenses providing crystal clear vision in bright sun.'],
                    ['name' => 'Aviator & Wayfarer Sunglasses', 'slug' => 'aviator-wayfarer-sunglasses', 'description' => 'Iconic metal and acetate retro sunglass frames.'],
                    ['name' => 'Blue Light Blocking Glasses', 'slug' => 'blue-light-blocking-glasses', 'description' => 'Protective clear lenses reducing digital screen eye strain.'],
                    ['name' => 'Sports Wraparound Sunglasses', 'slug' => 'sports-wraparound-sunglasses', 'description' => 'Impact-resistant aerodynamic shades for cycling and running.'],
                ]
            ],
            [
                'name' => 'Jewelry & Fashion Ornaments',
                'slug' => 'jewelry-fashion-ornaments',
                'description' => 'Gold-plated necklaces, silver rings, delicate earrings, and charm bracelets.',
                'subcategories' => [
                    ['name' => 'Gold Plated Necklaces', 'slug' => 'gold-plated-necklaces', 'description' => 'Layered pendants, chokers, and 18K gold-dipped chains.'],
                    ['name' => 'Sterling Silver Rings', 'slug' => 'sterling-silver-rings', 'description' => '925 silver band rings and solitaire cubic zirconia rings.'],
                    ['name' => 'Drop & Stud Earrings', 'slug' => 'drop-stud-earrings', 'description' => 'Sparkling crystal studs and festive traditional jhumkas.'],
                    ['name' => 'Chain Bracelets & Bangles', 'slug' => 'chain-bracelets-bangles', 'description' => 'Adjustable charm bracelets and engraved cuff bangles.'],
                ]
            ],
            [
                'name' => 'Kitchen Appliances',
                'slug' => 'kitchen-appliances',
                'description' => 'Air fryers, blenders, microwave ovens, electric kettles, and rice cookers.',
                'subcategories' => [
                    ['name' => 'Air Fryers & Halogen Ovens', 'slug' => 'air-fryers-halogen-ovens', 'description' => 'Rapid hot-air fryers for healthy oil-free cooking.'],
                    ['name' => 'Microwave & Convection Ovens', 'slug' => 'microwave-convection-ovens', 'description' => 'Digital microwave ovens with defrost and grill functions.'],
                    ['name' => 'Blenders & Food Processors', 'slug' => 'blenders-food-processors', 'description' => 'Multi-speed heavy duty spice grinders and smoothie makers.'],
                    ['name' => 'Electric Kettles & Rice Cookers', 'slug' => 'electric-kettles-rice-cookers', 'description' => 'Quick-boil stainless kettles and non-stick rice cookers.'],
                ]
            ],
            [
                'name' => 'Large Home Appliances',
                'slug' => 'large-home-appliances',
                'description' => 'Inverter air conditioners, refrigerators, washing machines, and vacuum cleaners.',
                'subcategories' => [
                    ['name' => 'Inverter Air Conditioners', 'slug' => 'inverter-air-conditioners', 'description' => 'Energy-saving split AC units with ionizer filters.'],
                    ['name' => 'Frost-Free Refrigerators', 'slug' => 'frost-free-refrigerators', 'description' => 'Double door and side-by-side refrigerators with fast cooling.'],
                    ['name' => 'Front Load Washing Machines', 'slug' => 'front-load-washing-machines', 'description' => 'Smart inverter washers with steam sanitization.'],
                    ['name' => 'Robot & Cordless Vacuum Cleaners', 'slug' => 'robot-cordless-vacuum-cleaners', 'description' => 'LiDAR navigation robot vacuums and stick cleaners.'],
                ]
            ],
            [
                'name' => 'Living Room Furniture',
                'slug' => 'living-room-furniture',
                'description' => 'Sofa sets, modern coffee tables, TV media consoles, and accent chairs.',
                'subcategories' => [
                    ['name' => 'Sectional Sofa Sets', 'slug' => 'sectional-sofa-sets', 'description' => 'L-shaped cushioned fabric and leather living room couches.'],
                    ['name' => 'Wooden Coffee Tables', 'slug' => 'wooden-coffee-tables', 'description' => 'Solid oak and teak center tables with lower shelf storage.'],
                    ['name' => 'TV Media Consoles', 'slug' => 'tv-media-consoles', 'description' => 'Modern entertainment units with cable management.'],
                    ['name' => 'Accent Lounge Chairs', 'slug' => 'accent-lounge-chairs', 'description' => 'Scandinavian upholstered reading and armchairs.'],
                ]
            ],
            [
                'name' => 'Bedroom Furniture',
                'slug' => 'bedroom-furniture',
                'description' => 'Solid wood beds, spacious wardrobes, bedside tables, and dressing units.',
                'subcategories' => [
                    ['name' => 'King & Queen Wooden Beds', 'slug' => 'king-queen-wooden-beds', 'description' => 'Sturdy wooden bed frames with upholstered headboards.'],
                    ['name' => 'Wardrobes & Closets', 'slug' => 'wardrobes-closets', 'description' => '3-door and 4-door wardrobes with mirror and drawer units.'],
                    ['name' => 'Bedside Nightstands', 'slug' => 'bedside-nightstands', 'description' => 'Compact 2-drawer side tables with smooth glides.'],
                    ['name' => 'Dressing Tables & Mirrors', 'slug' => 'dressing-tables-mirrors', 'description' => 'Contemporary vanity dressers with LED-lit mirrors.'],
                ]
            ],
            [
                'name' => 'Cookware & Tableware',
                'slug' => 'cookware-tableware',
                'description' => 'Non-stick fry pans, chef knives, ceramic dinner sets, and stainless water bottles.',
                'subcategories' => [
                    ['name' => 'Non-Stick Cookware Sets', 'slug' => 'non-stick-cookware-sets', 'description' => 'PFOA-free granite-coated cooking pots and pans.'],
                    ['name' => 'Stainless Steel Chef Knives', 'slug' => 'stainless-steel-chef-knives', 'description' => 'High-carbon forged kitchen knife blocks and sets.'],
                    ['name' => 'Porcelain Dinner Sets', 'slug' => 'porcelain-dinner-sets', 'description' => '32-piece microwave-safe ceramic dinnerware sets.'],
                    ['name' => 'Thermal Water Bottles', 'slug' => 'thermal-water-bottles', 'description' => 'Double-wall vacuum insulated flasks keeping drinks cold.'],
                ]
            ],
            [
                'name' => 'Home Decor & Lighting',
                'slug' => 'home-decor-lighting',
                'description' => 'Canvas wall art, ambient floor lamps, indoor ceramic pots, and curtains.',
                'subcategories' => [
                    ['name' => 'Canvas Wall Art & Frames', 'slug' => 'canvas-wall-art-frames', 'description' => 'Abstract and calligraphy wall art prints with black frames.'],
                    ['name' => 'Nordic Floor Lamps', 'slug' => 'nordic-floor-lamps', 'description' => 'Minimalist arc and tripod standing ambient lamps.'],
                    ['name' => 'Ceramic Plant Pots', 'slug' => 'ceramic-plant-pots', 'description' => 'Handmade indoor planters with drainage saucers.'],
                    ['name' => 'Velvet Curtains & Drapes', 'slug' => 'velvet-curtains-drapes', 'description' => 'Thermal insulated blackout window curtain panels.'],
                ]
            ],
            [
                'name' => 'Bedding & Mattresses',
                'slug' => 'bedding-mattresses',
                'description' => 'Memory foam mattresses, pure cotton bedsheets, comforters, and pillows.',
                'subcategories' => [
                    ['name' => 'Orthopedic Mattresses', 'slug' => 'orthopedic-mattresses', 'description' => 'High-density pocket spring and memory foam spine support mattresses.'],
                    ['name' => 'Pure Cotton Bedsheet Sets', 'slug' => 'pure-cotton-bedsheet-sets', 'description' => '300-thread count king size fitted sheets with pillowcases.'],
                    ['name' => 'Microfiber Comforters & Duvets', 'slug' => 'microfiber-comforters-duvets', 'description' => 'All-season hypoallergenic down-alternative quilts.'],
                    ['name' => 'Memory Foam Pillows', 'slug' => 'memory-foam-pillows', 'description' => 'Ergonomic neck contour cooling gel sleeping pillows.'],
                ]
            ],
            [
                'name' => 'Skincare & Treatments',
                'slug' => 'skincare-treatments',
                'description' => 'Gentle cleansers, targeted serums, broad-spectrum sunscreens, and moisturizers.',
                'subcategories' => [
                    ['name' => 'Hydrating Facial Cleansers', 'slug' => 'hydrating-facial-cleansers', 'description' => 'pH-balanced foaming and cream cleansers.'],
                    ['name' => 'Vitamin C & Retinol Serums', 'slug' => 'vitamin-c-retinol-serums', 'description' => 'Anti-aging and brightening antioxidant active serums.'],
                    ['name' => 'Sunscreens SPF 50+', 'slug' => 'sunscreens-spf-50', 'description' => 'Invisible matte finish PA++++ broad-spectrum sunscreens.'],
                    ['name' => 'Barrier Repair Moisturizers', 'slug' => 'barrier-repair-moisturizers', 'description' => 'Ceramide and hyaluronic acid hydrating face creams.'],
                ]
            ],
            [
                'name' => 'Hair Care & Styling',
                'slug' => 'hair-care-styling',
                'description' => 'Anti-hairfall shampoos, deep conditioners, hair growth serums, and dryers.',
                'subcategories' => [
                    ['name' => 'Anti-Dandruff Shampoos', 'slug' => 'anti-dandruff-shampoos', 'description' => 'Salicylic acid and zinc pyrithione clarifying shampoos.'],
                    ['name' => 'Deep Repair Hair Masks', 'slug' => 'deep-repair-hair-masks', 'description' => 'Keratin and argan oil intensive hair conditioning treatments.'],
                    ['name' => 'Nourishing Hair Oils', 'slug' => 'nourishing-hair-oils', 'description' => 'Cold-pressed onion and rosemary hair growth oils.'],
                    ['name' => 'Ionic Hair Dryers', 'slug' => 'ionic-hair-dryers', 'description' => 'Fast-drying ionic blowout dryers with diffuser nozzles.'],
                ]
            ],
            [
                'name' => 'Fragrances & Perfumes',
                'slug' => 'fragrances-perfumes',
                'description' => 'Eau de parfum, floral scents, woody oriental colognes, and refreshing body mists.',
                'subcategories' => [
                    ['name' => 'Men\'s Luxury Eau de Parfum', 'slug' => 'mens-luxury-eau-de-parfum', 'description' => 'Cedarwood, bergamot, and amber spicy masculine scents.'],
                    ['name' => 'Women\'s Floral Perfumes', 'slug' => 'womens-floral-perfumes', 'description' => 'Rose, jasmine, and vanilla romantic eau de parfum.'],
                    ['name' => 'Unisex Niche Fragrances', 'slug' => 'unisex-niche-fragrances', 'description' => 'Oud, leather, and smoked incense artisanal perfumes.'],
                    ['name' => 'Long-Lasting Body Mists', 'slug' => 'long-lasting-body-mists', 'description' => 'Refreshing daily fruit and floral spritz sprays.'],
                ]
            ],
            [
                'name' => 'Men\'s Grooming & Shaving',
                'slug' => 'mens-grooming-shaving',
                'description' => 'Electric beard trimmers, foil shavers, precision clippers, and shaving balms.',
                'subcategories' => [
                    ['name' => 'Electric Beard Trimmers', 'slug' => 'electric-beard-trimmers', 'description' => 'Rechargeable cordless trimmers with titanium coated blades.'],
                    ['name' => 'Foil & Rotary Shavers', 'slug' => 'foil-rotary-shavers', 'description' => 'Wet and dry close shaving electric shavers.'],
                    ['name' => 'Precision Hair Clippers', 'slug' => 'precision-hair-clippers', 'description' => 'Barber grade haircutting clippers with guide combs.'],
                    ['name' => 'Shaving Creams & Balms', 'slug' => 'shaving-creams-balms', 'description' => 'Soothing aloe vera aftershave balms and rich lather creams.'],
                ]
            ],
            [
                'name' => 'Bath & Body Care',
                'slug' => 'bath-body-care',
                'description' => 'Moisturizing body washes, body butters, exfoliating scrubs, and hand washes.',
                'subcategories' => [
                    ['name' => 'Moisturizing Body Washes', 'slug' => 'moisturizing-body-washes', 'description' => 'Sulfate-free hydrating shower gels with essential oils.'],
                    ['name' => 'Shea Butter Body Lotions', 'slug' => 'shea-butter-body-lotions', 'description' => 'Rich 24-hour nourishment lotions for dry skin.'],
                    ['name' => 'Exfoliating Body Scrubs', 'slug' => 'exfoliating-body-scrubs', 'description' => 'Himalayan salt and coffee smoothing body polishers.'],
                    ['name' => 'Organic Hand Soaps', 'slug' => 'organic-hand-soaps', 'description' => 'Antibacterial liquid hand soaps with botanical extracts.'],
                ]
            ],
            [
                'name' => 'Daily Groceries & Staples',
                'slug' => 'daily-groceries-staples',
                'description' => 'Premium aromatic rice, pure cooking oils, organic spices, and lentils.',
                'subcategories' => [
                    ['name' => 'Aromatic Kalijeera Rice', 'slug' => 'aromatic-kalijeera-rice', 'description' => 'Aromatic fine rice for traditional polao and biryani.'],
                    ['name' => 'Pure Mustard Oil', 'slug' => 'pure-mustard-oil', 'description' => 'Cold-pressed authentic mustard oil with pungent aroma.'],
                    ['name' => 'Organic Spices & Masalas', 'slug' => 'organic-spices-masalas', 'description' => 'Turmeric, chili powder, cumin, and garam masala blends.'],
                    ['name' => 'Red Lentils & Daal', 'slug' => 'red-lentils-daal', 'description' => 'Cleaned premium masoor and moong dal for everyday meals.'],
                ]
            ],
            [
                'name' => 'Beverages & Juices',
                'slug' => 'beverages-juices',
                'description' => 'Fine Assam and green tea, roasted coffee beans, fruit juices, and malt drinks.',
                'subcategories' => [
                    ['name' => 'Premium Black & Green Tea', 'slug' => 'premium-black-green-tea', 'description' => 'Sreemangal tea leaf blends and organic matcha green tea.'],
                    ['name' => 'Arabica Coffee Beans', 'slug' => 'arabica-coffee-beans', 'description' => 'Medium and dark roast whole coffee beans and ground blends.'],
                    ['name' => '100% Pure Fruit Juices', 'slug' => '100-pure-fruit-juices', 'description' => 'No-sugar-added orange, apple, and mango juices.'],
                    ['name' => 'Instant Energy Drinks', 'slug' => 'instant-energy-drinks', 'description' => 'Refreshing electrolyte powders and canned energy boosters.'],
                ]
            ],
            [
                'name' => 'Snacks & Confectionery',
                'slug' => 'snacks-confectionery',
                'description' => 'Imported chocolates, gourmet potato crisps, roasted dry fruits, and biscuits.',
                'subcategories' => [
                    ['name' => 'Imported Dark Chocolates', 'slug' => 'imported-dark-chocolates', 'description' => 'Swiss and Belgian 70% cocoa luxury dark chocolate bars.'],
                    ['name' => 'Gourmet Potato Crisps', 'slug' => 'gourmet-potato-crisps', 'description' => 'Kettle-cooked salted and sour cream potato chips.'],
                    ['name' => 'Roasted Cashews & Almonds', 'slug' => 'roasted-cashews-almonds', 'description' => 'Lightly salted California almonds and premium cashew nuts.'],
                    ['name' => 'Artisan Butter Biscuits', 'slug' => 'artisan-butter-biscuits', 'description' => 'Danish butter cookies and crisp digestive biscuits.'],
                ]
            ],
            [
                'name' => 'Baby Care & Maternity',
                'slug' => 'baby-care-maternity',
                'description' => 'Ultra-absorbent diapers, gentle baby wipes, baby lotions, and feeding bottles.',
                'subcategories' => [
                    ['name' => 'Ultra-Dry Baby Diapers', 'slug' => 'ultra-dry-baby-diapers', 'description' => 'Breathable taped and pant diapers with 12-hour leak lock.'],
                    ['name' => 'Sensitive Baby Wipes', 'slug' => 'sensitive-baby-wipes', 'description' => '99% pure water fragrance-free hypoallergenic wet wipes.'],
                    ['name' => 'Baby Skincare Lotion', 'slug' => 'baby-skincare-lotion', 'description' => 'Mild formula baby massage oil and daily moisturizing lotion.'],
                    ['name' => 'Anti-Colic Feeding Bottles', 'slug' => 'anti-colic-feeding-bottles', 'description' => 'BPA-free silicone nipple bottles preventing colic air bubbles.'],
                ]
            ],
            [
                'name' => 'Fitness & Home Gym',
                'slug' => 'fitness-home-gym',
                'description' => 'Adjustable dumbbells, latex resistance bands, non-slip yoga mats, and pull-up bars.',
                'subcategories' => [
                    ['name' => 'Adjustable Dumbbell Sets', 'slug' => 'adjustable-dumbbell-sets', 'description' => 'Quick-select cast iron dumbbell pairs up to 24kg.'],
                    ['name' => 'Resistance Exercise Bands', 'slug' => 'resistance-exercise-bands', 'description' => 'Heavy duty loop bands for strength and mobility training.'],
                    ['name' => 'Non-Slip Yoga Mats', 'slug' => 'non-slip-yoga-mats', 'description' => 'Eco-friendly 6mm high-grip TPE pilates and yoga mats.'],
                    ['name' => 'Doorway Pull-Up Bars', 'slug' => 'doorway-pull-up-bars', 'description' => 'No-screw heavy gauge doorway chin-up bars.'],
                ]
            ],
            [
                'name' => 'Sports & Outdoor Gear',
                'slug' => 'sports-outdoor-gear',
                'description' => 'Cricket bats, footballs, badminton rackets, and waterproof camping tents.',
                'subcategories' => [
                    ['name' => 'English Willow Cricket Bats', 'slug' => 'english-willow-cricket-bats', 'description' => 'Grade 1 English willow bats with massive sweet spots.'],
                    ['name' => 'Professional Footballs', 'slug' => 'professional-footballs', 'description' => 'FIFA-certified thermally bonded match soccer balls.'],
                    ['name' => 'Carbon Fiber Badminton Rackets', 'slug' => 'carbon-fiber-badminton-rackets', 'description' => 'Lightweight high-tension isometric head badminton rackets.'],
                    ['name' => 'Waterproof Camping Tents', 'slug' => 'waterproof-camping-tents', 'description' => '4-person double layer pop-up tents for camping expeditions.'],
                ]
            ],
            [
                'name' => 'Automotive & Motorbike',
                'slug' => 'automotive-motorbike',
                'description' => 'Certified motorcycle helmets, synthetic motor oil, car shampoos, and dash cameras.',
                'subcategories' => [
                    ['name' => 'DOT Certified Helmets', 'slug' => 'dot-certified-helmets', 'description' => 'Aerodynamic full-face helmets with dual-visor protection.'],
                    ['name' => 'Full Synthetic Motor Oils', 'slug' => 'full-synthetic-motor-oils', 'description' => '10W-40 and 5W-30 engine oils for high performance.'],
                    ['name' => 'Car Detailing & Wash Kits', 'slug' => 'car-detailing-wash-kits', 'description' => 'Snow foam car shampoos, microfiber towels, and ceramic wax.'],
                    ['name' => 'High-Definition Dash Cams', 'slug' => 'high-definition-dash-cams', 'description' => 'Dual front and rear 4K dashcams with parking surveillance.'],
                ]
            ],
            [
                'name' => 'Books & Stationery',
                'slug' => 'books-stationery',
                'description' => 'Bestselling novels, business self-help, hardcover dot journals, and fountain pens.',
                'subcategories' => [
                    ['name' => 'Contemporary Bestselling Fiction', 'slug' => 'contemporary-bestselling-fiction', 'description' => 'Award winning novels, thrillers, and literary fiction.'],
                    ['name' => 'Business & Personal Growth', 'slug' => 'business-personal-growth', 'description' => 'Finance, leadership, productivity, and psychology books.'],
                    ['name' => 'Hardcover Dot-Grid Journals', 'slug' => 'hardcover-dot-grid-journals', 'description' => '120gsm bleed-proof hardcover bullet journals.'],
                    ['name' => 'Fine Fountain Pens', 'slug' => 'fine-fountain-pens', 'description' => 'Precision stainless nib fountain pens with piston converters.'],
                ]
            ],
            [
                'name' => 'Musical Instruments',
                'slug' => 'musical-instruments',
                'description' => 'Acoustic guitars, electronic keyboards, studio USB mics, and ukuleles.',
                'subcategories' => [
                    ['name' => 'Acoustic Folk Guitars', 'slug' => 'acoustic-folk-guitars', 'description' => 'Spruce top dreadnought guitars with warm acoustic resonance.'],
                    ['name' => '61-Key Digital Keyboards', 'slug' => '61-key-digital-keyboards', 'description' => 'Touch-sensitive portable piano keyboards with learning modes.'],
                    ['name' => 'USB Condenser Microphones', 'slug' => 'usb-condenser-microphones', 'description' => 'Cardioid studio recording microphones for podcasts and music.'],
                    ['name' => 'Soprano Ukuleles', 'slug' => 'soprano-ukuleles', 'description' => 'Mahogany wood soprano ukuleles with Aquila strings.'],
                ]
            ],
            [
                'name' => 'Pet Care & Supplies',
                'slug' => 'pet-care-supplies',
                'description' => 'Dry cat food, puppy kibble, bentonite cat litter, and grooming shampoos.',
                'subcategories' => [
                    ['name' => 'Nutritional Dry Cat Food', 'slug' => 'nutritional-dry-cat-food', 'description' => 'Tuna and ocean fish high-protein formula for adult cats.'],
                    ['name' => 'Premium Dog Kibble', 'slug' => 'premium-dog-kibble', 'description' => 'Real chicken and rice balanced kibble for active dogs.'],
                    ['name' => 'Clumping Cat Litter', 'slug' => 'clumping-cat-litter', 'description' => 'Natural bentonite fast-clumping odor control cat litter.'],
                    ['name' => 'Gentle Pet Shampoos', 'slug' => 'gentle-pet-shampoos', 'description' => 'Hypoallergenic oatmeal and aloe vera pet cleansing shampoos.'],
                ]
            ],
        ];
    }
}
