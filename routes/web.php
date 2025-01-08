<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactonsController;

Route::get('/', function () {
    return view('welcome');
});


// ---- TEST ROUTES ----
// calls function index in Transactions controller
// http://localhost:8000/test
// page with "Transactions Controller (test).
Route::get('/test', 'App\Http\Controllers\TransactionsController@test');

// pass a value to a page
//  http://localhost:8000/passname
// page with view passname, sending name "Account List Header"
Route::get('/passname', function () {
    return view('passname',['name'=>'Account List Header']);
});


// ---- ACCOUNTS ROUTES ---- 
Route::get('/accounts/assets', 'App\Http\Controllers\TransactionsController@assets')->name('assets');
// set lastBalanced value to today's date for all cleared transactions for this account
Route::get('/accounts/{accountName}/balances', 'App\Http\Controllers\TransactionsController@balances')->name('balances');
// Transactions for account accountName with trans_dates in the date range given
Route::get('/accounts/{accountName}/upload', 'App\Http\Controllers\TransactionsController@upload');
Route::get('/accounts/{accountName}/{beginDate}/{endDate}', 'App\Http\Controllers\TransactionsController@transactions')->name('transactions');
// All this year's transactions (looking at trans_date) for account accountName
Route::get('/accounts/{accountName}/{beginDate}/{endDate}/{clearedBalance}/{registerBalance}/{lastBalanced}', 'App\Http\Controllers\TransactionsController@transactions')->name('transactions');
// List of accounts with balances (cleared & register), Last Balanced, and button to see transactions for that account
//      includes line "all" for all transactions
Route::get('/accounts', 'App\Http\Controllers\TransactionsController@index')->name('accounts');
// alternate way:
// Route::get('/accounts', [TransactionsController::class, 'index']);


// ---- TRANSACTIONS  ROUTES ----
Route::post('/transactions/delete/{id}', 'App\Http\Controllers\TransactionsController@delete');
Route::put('/transactions/update', 'App\Http\Controllers\TransactionsController@update');
Route::post('/transactions/insertTrans', 'App\Http\Controllers\TransactionsController@insert');
Route::post('/transactions/insertAlias/{origToFrom}/{newValue}', 'App\Http\Controllers\TransactionsController@insertAlias');
Route::get('/transactions/totalKey/{total_key}', 'App\Http\Controllers\TransactionsController@totalKey');
Route::get('/transactions/getDefaults/{toFrom}', 'App\Http\Controllers\TransactionsController@getDefaults');
Route::get('/transactions/add', 'App\Http\Controllers\TransactionsController@addTransaction')->name("addTransaction");
Route::post('/transactions/insert', 'App\Http\Controllers\TransactionsController@writeTransaction')->name("writeTransaction");

Route::get('/temp/splitMandM', 'App\Http\Controllers\TransactionsController@splitMandM');
?>