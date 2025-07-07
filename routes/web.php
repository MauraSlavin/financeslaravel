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
// see list of assets & most recent values
Route::get('/accounts/assets', 'App\Http\Controllers\TransactionsController@assets')->name('assets');
// process GB Limo paycheck - ask for data needed to write database records
Route::get('/accounts/gblimo', 'App\Http\Controllers\TransactionsController@gblimo')->name('gblimo');
// update investments balances
Route::get('/accounts/investmentsindex', 'App\Http\Controllers\TransactionsController@investmentsindex')->name('investmentsindex');
// see buckets (in Dsc Svg )
Route::get('/accounts/buckets', 'App\Http\Controllers\TransactionsController@buckets')->name('buckets');
// see budget
Route::get('/accounts/budget', 'App\Http\Controllers\TransactionsController@budget')->name('budget');
// see budget & actuals (& diff)
Route::get('/accounts/budgetactuals', 'App\Http\Controllers\TransactionsController@budgetactuals')->name('budgetactuals');
// see actuals
Route::get('/accounts/actuals', 'App\Http\Controllers\TransactionsController@actuals')->name('actuals');
// screen to initiate moving money between buckets
Route::get('/accounts/moveBuckets', 'App\Http\Controllers\TransactionsController@moveBuckets')->name('moveBuckets');
// write records to move the funds betwen buckets
Route::post('/accounts/moveFundsBetweenBuckets', 'App\Http\Controllers\TransactionsController@moveFundsBetweenBuckets')->name('moveFundsBetweenBuckets');
// see spending for Mike/Maura
Route::get('/accounts/spending/{who}', 'App\Http\Controllers\TransactionsController@spending')->name('spending');
// Monthly transactions
Route::get('/accounts/monthly', 'App\Http\Controllers\TransactionsController@monthly')->name('monthly');
// calc cost for a trip
Route::get('/accounts/trips', 'App\Http\Controllers\TransactionsController@trips')->name('trips');
// sum tolls for a specific trip, using records in tolls table
Route::post('/accounts/tallytolls', 'App\Http\Controllers\TransactionsController@tallytolls');
// write tolls from csv file to tolls table
Route::get('/accounts/uploadtolls', 'App\Http\Controllers\TransactionsController@uploadtolls');
// write transactions to transactions table
Route::post('/accounts/recordTrip', 'App\Http\Controllers\TransactionsController@recordTrip')->name('recordTrip');
// process GB Limo paycheck - write the records to the database
Route::post('/accounts/writegblimo', 'App\Http\Controllers\TransactionsController@writeGBLimo')->name("writegblimo");
// set lastBalanced value to today's date for all cleared transactions for this account
Route::get('/accounts/{accountName}/balances', 'App\Http\Controllers\TransactionsController@balances')->name('balances');
// upload transactions from a csv file (downloaded from bank's website) to transactions table
Route::get('/accounts/{accountName}/upload', 'App\Http\Controllers\TransactionsController@upload');
// Transactions for account accountName with trans_dates in the date range given
Route::get('/accounts/{accountName}/{beginDate}/{endDate}', 'App\Http\Controllers\TransactionsController@transactions')->name('transactions');
// All this year's transactions (looking at trans_date) for account accountName
Route::get('/accounts/{accountName}/{beginDate}/{endDate}/{clearedBalance}/{registerBalance}/{lastBalanced}', 'App\Http\Controllers\TransactionsController@transactions')->name('transactions');
// List of accounts with balances (cleared & register), Last Balanced, and button to see transactions for that account
//      includes line "all" for all transactions
Route::get('/accounts/{acctsMSg?}', 'App\Http\Controllers\TransactionsController@index')->name('accounts');
// "Dashboard"
Route::get('/', 'App\Http\Controllers\TransactionsController@index')->name('accounts');


// ---- TRANSACTIONS  ROUTES ----
Route::post('/transactions/delete/{id}', 'App\Http\Controllers\TransactionsController@delete');
Route::put('/transactions/update', 'App\Http\Controllers\TransactionsController@update');
Route::put('/transactions/updateInvBalances', 'App\Http\Controllers\TransactionsController@updateInvBalances');
Route::post('/transactions/insertTrans', 'App\Http\Controllers\TransactionsController@insert');
Route::post('/transactions/insertAlias/{origToFrom}/{newValue}', 'App\Http\Controllers\TransactionsController@insertAlias');
Route::get('/transactions/totalKey/{total_key}', 'App\Http\Controllers\TransactionsController@totalKey');
Route::get('/transactions/getDefaults/{account}/{toFrom}', 'App\Http\Controllers\TransactionsController@getDefaults');
Route::get('/transactions/add', 'App\Http\Controllers\TransactionsController@addTransaction')->name("addTransaction");
Route::post('/transactions/insert', 'App\Http\Controllers\TransactionsController@writeTransaction')->name("writeTransaction");
Route::get('/transactions/monthlies', 'App\Http\Controllers\TransactionsController@writeMonthlyTransactions')->name('writeMonthlyTransactions');
Route::get('/temp/splitMandM', 'App\Http\Controllers\TransactionsController@splitMandM');
// save changes to monthly transaction
Route::put('/transactions/saveMonthly/{id}', 'App\Http\Controllers\TransactionsController@saveMonthly')->name('saveMonthly');
?>