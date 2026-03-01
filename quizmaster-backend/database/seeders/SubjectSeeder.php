<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'General Knowledge', 'description' => 'Broad range of general topics'],
            ['name' => 'Mathematics', 'description' => 'Numbers, algebra, geometry, and logic'],
            ['name' => 'Science', 'description' => 'Physics, chemistry, biology, and earth science'],
            ['name' => 'Technology', 'description' => 'Computers, programming, and modern technology'],
            ['name' => 'History', 'description' => 'World history, civilizations, and events'],
            ['name' => 'Geography', 'description' => 'Countries, capitals, maps, and physical geography'],
            ['name' => 'Language & Literature', 'description' => 'Grammar, vocabulary, and literary works'],
        ];

        foreach ($subjects as $data) {
            Subject::firstOrCreate(['name' => $data['name']], $data);
        }

        // Assign existing questions to subjects based on their category field
        $categoryMap = [
            'math' => 'Mathematics',
            'mathematics' => 'Mathematics',
            'science' => 'Science',
            'physics' => 'Science',
            'chemistry' => 'Science',
            'biology' => 'Science',
            'technology' => 'Technology',
            'tech' => 'Technology',
            'programming' => 'Technology',
            'computer' => 'Technology',
            'history' => 'History',
            'geography' => 'Geography',
            'language' => 'Language & Literature',
            'literature' => 'Language & Literature',
            'english' => 'Language & Literature',
        ];

        $subjectCache = [];

        Question::whereNull('subject_id')->get()->each(function ($question) use ($categoryMap, &$subjectCache) {
            $category = strtolower(trim($question->category ?? ''));
            $subjectName = null;

            foreach ($categoryMap as $keyword => $name) {
                if (str_contains($category, $keyword)) {
                    $subjectName = $name;
                    break;
                }
            }

            if (!$subjectName) {
                $subjectName = 'General Knowledge';
            }

            if (!isset($subjectCache[$subjectName])) {
                $subjectCache[$subjectName] = Subject::where('name', $subjectName)->value('id');
            }

            $question->update(['subject_id' => $subjectCache[$subjectName]]);
        });
    }
}
