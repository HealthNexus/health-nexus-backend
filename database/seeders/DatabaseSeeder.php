<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\Post;
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

        //create random users
        User::factory()->count(10)->create();


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
            $dis = \App\Models\Disease::create(['name' => $disease, 'slug' => \Illuminate\Support\Str::slug($disease)]);
            $id = random_int(1, 10);
            $dis->patients()->attach(floor($id));
        }

        $patient = User::find(2);
        $patient->diseases()->attach(3);

        Post::factory()->count(10)->create();
    }
}
