<?php
use Illuminate\Database\Seeder;

/**
 * Created by PhpStorm.
 * User: brnbp
 * Date: 6/14/16
 * Time: 10:21 PM
 */
class UsersTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'username' => 'bruno2546',
                'email' => 'bruno2546@brnbp.me',
                'password' => bcrypt('bruno2546'),
                'remember_token' => md5('bruno2546')
            ],
            [
                'username' => 'brnbp',
                'email' => 'brnbp@brnbp.me',
                'password' => bcrypt('brnbp'),
                'remember_token' => md5('brnbp')
            ]
        ]);    
    }
}