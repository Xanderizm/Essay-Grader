<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::first();

        if (! $dosen) {
            $this->command->error('Belum ada akun dosen di tabel users.');
            return;
        }

        $exam = Exam::create([
            'user_id'         => $dosen->id,
            'title'           => 'UTS Basis Data (Contoh Dinamis)',
            'gform_url'       => 'https://docs.google.com/forms/d/DUMMY/edit',
            'gform_id'        => 'DUMMY_' . Str::random(8),
            'analysis_status' => 'success',
            'analyzed_at'     => now(),
            'status'          => 'active',
            'webhook_token'   => Str::random(40),
        ]);

        // Kolom identitas
        Question::create(['exam_id' => $exam->id, 'question_text' => 'Nama Lengkap', 'field_type' => 'identity', 'identity_role' => 'name', 'gform_item_type' => 'short_answer', 'order' => 1]);
        Question::create(['exam_id' => $exam->id, 'question_text' => 'NIM', 'field_type' => 'identity', 'identity_role' => 'nim', 'gform_item_type' => 'short_answer', 'order' => 2]);
        Question::create(['exam_id' => $exam->id, 'question_text' => 'Kelas', 'field_type' => 'identity', 'identity_role' => 'class', 'gform_item_type' => 'short_answer', 'order' => 3]);

        // Dua soal esai
        $q1 = Question::create([
            'exam_id'         => $exam->id,
            'question_text'   => 'Jelaskan apa yang dimaksud dengan normalisasi database dan sebutkan tujuan utamanya!',
            'answer_key'      => 'Normalisasi adalah proses menyusun tabel dalam basis data untuk menghilangkan redundansi (duplikasi data) dan mencegah anomali (insert, update, delete), sehingga data konsisten dan integritasnya terjaga.',
            'field_type'      => 'essay',
            'gform_item_type' => 'paragraph',
            'order'           => 4,
        ]);

        $q2 = Question::create([
            'exam_id'         => $exam->id,
            'question_text'   => 'Jelaskan perbedaan primary key dan foreign key!',
            'answer_key'      => 'Primary key adalah kolom yang mengidentifikasi setiap baris secara unik dan tidak boleh kosong atau duplikat. Foreign key adalah kolom yang merujuk ke primary key tabel lain untuk menghubungkan dua tabel dan menjaga integritas referensial.',
            'field_type'      => 'essay',
            'gform_item_type' => 'paragraph',
            'order'           => 5,
        ]);

        // Kolom lain di Form yang tidak dinilai
        Question::create(['exam_id' => $exam->id, 'question_text' => 'Saya menyatakan mengerjakan secara mandiri', 'field_type' => 'ignored', 'gform_item_type' => 'checkbox', 'order' => 6]);

        // Tiga mahasiswa: jawaban baik, setengah benar, dan ngawur
        $mahasiswa = [
            ['2201001', 'Andi Pratama', [
                'Normalisasi itu cara merapikan struktur tabel supaya tidak ada data kembar, jadi data tetap konsisten dan tidak bermasalah saat ditambah, diubah, atau dihapus.',
                'Primary key itu penanda unik tiap baris dan tidak boleh kosong. Foreign key adalah kolom yang menunjuk ke primary key tabel lain sehingga dua tabel saling terhubung.',
            ]],
            ['2201002', 'Budi Santoso', [
                'Normalisasi adalah proses memcah tabel supaya redudansi data berkurang.',
                'Primary key itu kunci utama, foreign key itu kunci tambahan.',
            ]],
            ['2201003', 'Citra Lestari', [
                'Normalisasi adalah cara memasak nasi goreng agar lebih enak.',
                'Saya tidak tahu.',
            ]],
        ];

        foreach ($mahasiswa as $i => [$nim, $nama, $jawaban]) {
            $submission = Submission::create([
                'exam_id'          => $exam->id,
                'gform_response_id' => 'dummy-' . ($i + 1),
                'nim'              => $nim,
                'name'             => $nama,
                'class'            => 'A',
                'submitted_at'     => now(),
            ]);

            foreach ([$q1, $q2] as $j => $q) {
                StudentAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id'   => $q->id,
                    'raw_answer'    => $jawaban[$j],
                ]);
            }
        }
    }
}