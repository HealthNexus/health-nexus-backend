<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Disease;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\Hospital;
use App\Models\Post;
use App\Models\Symptom;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //create hospitals
        Hospital::factory()->count(10)->create();

        //create Doctors
        Doctor::factory()->count(10)->create();

        //create random users
        User::factory()->count(10)->create();

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
        foreach ($categories as $category) {
            Category::factory()->create(['name' => $category]);
        }

        //create drugs
        Drug::factory()->count(10)->create();

        //create drug_categories
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
        foreach ($drug_categories as $category) {
            $dc = DrugCategory::factory()->create(['name' => $category]);

            //attach drugs to drug category
            $dc->drugs()->attach(rand(1, 10));
        }

        //create Symptoms
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
        foreach ($symptoms as $symptom) {
            Symptom::create(['description' => $symptom]);
        }


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
        foreach ($diseases as $disease) {
            $dis = Disease::create(['name' => $disease, 'slug' => \Illuminate\Support\Str::slug($disease)]);

            //attach pateints to disease
            $dis->patients()->attach(rand(1, 10));


            //attach a category
            $dis->categories()->attach(rand(1, 5));

            //attach drugs
            $dis->drugs()->attach(rand(1, 10));

            //attach symptoms
            $dis->symptoms()->attach(rand(1, count($symptoms)));
        }

        $patient = User::find(2);
        $patient->diseases()->attach(3);

        //create fake posts
        Post::factory()->count(10)->create();
    }
}
