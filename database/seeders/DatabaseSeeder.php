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
use Illuminate\Support\Str;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'Doctor',
            'Nurse',
            'Pharmacist',
            'Patient',
        ];
        //create categories
        $categories = [
            'Chronic Disease',
            'Infectious Disease',
            'Mental Health',
            'Cancer',
            'Heart Disease',
            'Respiratory Disease',
            'Gastrointestinal Disease',
        ];
        //create disease
        $diseases = [
            'Covid-19',
            'Malaria',
            'Typhoid',
            'HIV/AIDS', '
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
            'Fever',
            'Headache',
            'Cough',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
            'Diarrhea',
            'Nausea',
            'Vomiting',
            'Chills',
            'Congestion or Runny Nose',
            'Muscle Aches',
            'Sore Throat',
            'Fatigue',
            'Muscle Aches',
            'Shortness of Breath',
            'Loss of Taste or Smell',
        ];


        //create roles
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'slug' => Str::slug($role)]);
        }

        //create hospitals
        Hospital::factory()->count(10)->create();

        //create drugs
        Drug::factory()->count(10)->create();

        //create users
        User::factory()->count(10)->create();

        //create drug_categories
        foreach ($drug_categories as $category) {
            $dc = DrugCategory::factory()->create(['name' => $category]);

            //attach drugs to drug category
            $dc->drugs()->attach(rand(1, 10));
        }

        //create symptoms
        foreach ($symptoms as $symptom) {
            Symptom::create(['description' => $symptom]);
        }


        //create categories in database
        foreach ($categories as $category) {
            $category = Category::factory()->create(['name' => $category]);
        }

        // create diseases in database
        foreach ($diseases as $disease) {
            $dis = Disease::create(['name' => $disease, 'slug' => Str::slug($disease)]);

            //attach pateints to disease
            $dis->patients()->attach(rand(1, 10));

            //attach drugs
            $dis->drugs()->attach(rand(1, 10));

            //attach symptoms
            $dis->symptoms()->attach(rand(1, count($symptoms)));

            //attach categories
            $dis->categories()->attach(rand(1, count($categories)));
        }


        //create posts
        Post::factory()->count(5)->create();

        //create comments which will inturn create users and posts
        Comment::factory()->count(20)->create();

        //create replies
        Reply::factory()->count(50)->create();
    }
}
