<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Disease;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\Hospital;
use App\Models\Post;
use App\Models\Reply;
use App\Models\Role;
use App\Models\Symptom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        //roles
        $roles = [
            'Admin',
            'Patient',
        ];
        // //create categories
        $categories = [
            'Chronic Disease',
            'Infectious Disease',
            'Mental Health',
            'Cancer',
            'Heart Disease',
            'Respiratory Disease',
            'Gastrointestinal Disease',
        ];
        // //create disease
        $diseases = [
            'Covid-19',
            'Malaria',
            'Typhoid',
            'HIV/AIDS',
            '
                Diabetes',
            'Hypertension',
            'Cancer',
            'Tuberculosis',
            'Cholera',
            'Yellow Fever',
            'Measles',
            'Chicken Pox',
            'Lassa Fever',
            'Ebola',
            'Influenza',
            'Pneumonia',
            'Meningitis',
            'Hepatitis',
            'Dysentery',
            'Chronic Kidney Disease',
            'Heart Disease',
            'Stroke',
            'Asthma',
            'Arthritis',
        ];
        $drug_categories = [
            'Antibiotics',
            'Analgesics',
            'Antipyretics',
            'Antimalarial',
            'Antiviral',
            'Antifungal',
            'Antihistamines',
            'Antacids',
            'Antidiarrheal',
            'Antitussive',
            'Expectorants',
            'Decongestants',
            'Anticoagulants',
            'Anticonvulsants',
            'Antidepressants',
            'Antipsychotics',
            'Antianxiety',
            'Antihypertensive',
            'Antihyperlipidemic',
            'Antidiabetic',
            'Anticoagulants',
            'Anticonvulsants',
            'Antidepressants',
            'Antipsychotics',
            'Antianxiety',
            'Antihypertensive',
            'Antihyperlipidemic',
            'Antidiabetic',
            'Chronic Disease',
            'Infectious Disease',
            'Mental Health',
            'Cancer',
            'Heart Disease',
            'Respiratory Disease',
            'Gastrointestinal Disease',
        ];
        $symptoms = [
            "Fever",
            "Cough",
            "Shortness of breath",
            "Fatigue",
            "Muscle aches",
            "Headache",
            "Sore throat",
            "Runny nose",
            "Congestion",
            "Loss of taste",
            "Loss of smell",
            "Nausea",
            "Vomiting",
            "Diarrhea",
            "Chills",
            "Sweating",
            "Chest pain",
            "Abdominal pain",
            "Back pain",
            "Joint pain",
            "Rash",
            "Hives",
            "Itching",
            "Swelling",
            "Dizziness",
            "Lightheadedness",
            "Fainting",
            "Palpitations",
            "Blurred vision",
            "Double vision",
            "Eye pain",
            "Ear pain",
            "Hearing loss",
            "Tinnitus",
            "Sore muscles",
            "Weakness",
            "Numbness",
            "Tingling",
            "Difficulty swallowing",
            "Hoarseness",
            "Wheezing",
            "Coughing up blood",
            "Excessive thirst",
            "Frequent urination",
            "Night sweats",
            "Weight loss",
            "Weight gain",
            "Increased appetite",
            "Decreased appetite",
            "Restlessness",
            "Insomnia",
            "Daytime sleepiness",
            "Mood swings",
            "Anxiety",
            "Depression",
            "Irritability",
            "Confusion",
            "Memory loss",
            "Difficulty concentrating",
            "Seizures",
            "Tremors",
            "Balance problems",
            "Coordination problems",
            "Slurred speech",
            "Swollen glands",
            "Cold hands",
            "Cold feet",
            "Bruising",
            "Bleeding",
            "Dry skin",
            "Pale skin",
            "Yellow skin",
            "Red skin",
            "Purple spots",
            "Blue lips",
            "Blue fingernails",
            "Hair loss",
            "Excessive sweating",
            "Body odor",
            "Bad breath",
            "Dry mouth",
            "Sores in mouth",
            "Gum pain",
            "Tooth pain",
            "Bad taste",
            "Bloating",
            "Gas",
            "Heartburn",
            "Constipation",
            "Blood in stool",
            "Dark urine",
            "Frequent infections",
            "Slow healing",
            "High blood pressure",
            "Low blood pressure",
            "Rapid heartbeat",
            "Slow heartbeat",
            "Yellow eyes",
            "Red eyes",
            "Teary eyes",
            "Eye discharge"
        ];

        $drugs = [
            "Acetaminophen",
            "Ibuprofen",
            "Amoxicillin",
            "Azithromycin",
            "Ciprofloxacin",
            "Metformin",
            "Lisinopril",
            "Amlodipine",
            "Simvastatin",
            "Atorvastatin",
            "Omeprazole",
            "Metoprolol",
            "Losartan",
            "Albuterol",
            "Gabapentin",
            "Sertraline",
            "Hydrochlorothiazide",
            "Furosemide",
            "Prednisone",
            "Tramadol",
            "Clonazepam",
            "Citalopram",
            "Levothyroxine",
            "Montelukast",
            "Trazodone",
            "Pantoprazole",
            "Meloxicam",
            "Carvedilol",
            "Cyclobenzaprine",
            "Escitalopram",
            "Duloxetine",
            "Fluoxetine",
            "Bupropion",
            "Hydrocodone",
            "Zolpidem",
            "Cetirizine",
            "Loratadine",
            "Ranitidine",
            "Famotidine",
            "Doxycycline",
            "Clindamycin",
            "Morphine",
            "Oxycodone",
            "Methotrexate",
            "Warfarin",
            "Aspirin",
            "Insulin",
            "Diltiazem",
            "Propranolol"
        ];




        // //create roles
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'slug' => Str::slug($role)]);
        }

        //create hospitals
        $hospitals = [
            'Konfa Anokye Teaching Hospital',
            'Korle Bu Teaching Hospital',
            'Tamale Teaching Hospital',
        ];
        foreach ($hospitals as $hospital) {
            Hospital::create(['name' => $hospital, 'slug' => Str::slug($hospital)]);
        }

        // //create drugs
        foreach ($drugs as $drug) {
            Drug::factory()->create(['name' => $drug]);
        }

        // //create users
        User::factory()->count(9)->create();
        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin@gmail.com', 'role_id' => 1, 'password' => bcrypt('password')]);

        //create drug_categories
        foreach ($drug_categories as $category) {
            // Check if category already exists, create only if it doesn't
            $dc = DrugCategory::firstOrCreate(
                ['name' => $category],
                [
                    'name' => $category,
                    'slug' => \Illuminate\Support\Str::slug($category),
                    'description' => "Category for {$category} medications"
                ]
            );

            $drug_id = rand(1, 10);
            //attach drugs to drug category (avoid duplicate attachments)
            $dc->drugs()->syncWithoutDetaching($drug_id);
        }

        //create symptoms
        foreach ($symptoms as $symptom) {
            Symptom::create(['description' => $symptom]);
        }


        //create categories in database
        foreach ($categories as $category) {
            $category = Category::factory()->create(['name' => $category]);
        }

        // // create diseases in database
        foreach ($diseases as $disease) {
            $dis = Disease::create(['name' => $disease, 'slug' => Str::slug($disease)]);

            //attach pateints to disease
            $rand = rand(1, 10);
            $dis->patients()->attach($rand);

            //attach drugs
            $dis->drugs()->attach($rand);

            //attach symptoms
            $id = rand(1, count($symptoms));
            $dis->symptoms()->attach($id);
            $id = rand(1, count($symptoms));
            $dis->symptoms()->attach($id);
            $id = rand(1, count($symptoms));
            $dis->symptoms()->attach($id);
            $id = rand(1, count($symptoms));
            $dis->symptoms()->attach($id);

            //attach categories
            $categoryId = rand(1, count($categories));
            $dis->categories()->attach($categoryId);
            $categoryId = rand(1, count($categories));
            $dis->categories()->attach($categoryId);
        }

        // Attach 500 diseases to the user (using the same disease ID for simplicity)
        for ($i = 1; $i < 10; $i++) {
            $diseaseId = $i; // Replace with the actual disease ID you want to attach

            // Attach the disease
            $admin->diseases()->attach($diseaseId);

            // Calculate a random created_at timestamp within the last 6 years
            $createdAt = Carbon::now()->subYears(6)->addDays(rand(0, 365 * 6));

            // Update the pivot table with the new created_at timestamp
            DB::table('disease_user')
                ->where('user_id', $admin->id)
                ->where('disease_id', $diseaseId)
                ->orderBy('created_at', 'desc') // Ensure we get the latest record if there are duplicates
                ->limit(1) // Ensure only the latest record is updated
                ->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);
        }

        //create posts
        Post::factory()->count(10)->create();


        $users = User::all()->pluck('id')->toArray();
        $diseaseIds = Disease::pluck('id')->toArray(); // Get all disease IDs

        $totalDiseasesPerYear = 1000;
        $monthRange = range(1, 12);
        foreach (range(2022, 2023) as $year) {
            for ($i = 0; $i < $totalDiseasesPerYear; $i++) {
                $userId = $users[array_rand($users)]; // Select a random user ID
                $diseaseId = $diseaseIds[array_rand($diseaseIds)]; // Select a random disease ID
                $month = $monthRange[array_rand($monthRange)]; // Select a random month
                //2024 can only add diseases up to the 8th month
                if ($year == 2024 && $month > 8) {
                    continue;
                }
                $date = Carbon::create($year, $month, rand(1, 28)); // Random day in the selected month

                // Attach the disease with a specific created_at and updated_at date
                User::find($userId)->diseases()->attach($diseaseId, [
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // Seed delivery areas
        $this->call(DeliveryAreaSeeder::class);
    }
}
