<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Monthly;
use Illuminate\Support\Facades\Route;


use Carbon\Carbon;

class TransactionsController extends Controller
{

    // page with "Transactions Controller (test).
    public function test() {
        echo "<br>Transactions Controller (test).";
    }


    // beginDate defaults to a month ago
    // end date defaults to today
    public function setDefaultBeginEndDates($beginDate = null, $endDate = null) {
        
        // end date defaults to today
        if($endDate == null) {
            $endDate = date("Y-m-d");
        }

        // beginDate defaults to a month ago
        if($beginDate == null) {
            $beginDate = date('Y-m-d', strtotime('-45 day'));
        }

        return [$beginDate, $endDate];
    }


    // get transactions from csv file
    public function readUploadCsv($accountName) {

        // csv files are expected to be in the public/uploadFiles folder.
        $fullFilePath = public_path('uploadFiles/' . strtolower($accountName) . '.csv');

        // does the csv file exist?
        if (!file_exists($fullFilePath)) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' not found'], 404);
        }

        // open the file
        $handle = fopen($fullFilePath, 'r');

        // ignore first 3 lines of VISA csv file
        if($accountName == 'VISA') {
            for($i = 1; $i <= 3; $i++) $garbage = fgetcsv($handle, 1000, ',');
        }

        // get header row
        $headers = fgetcsv($handle, 1000, ',');

        // make sure headers exist
        if($headers === FALSE) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' has no headers'], 411);
        }

        // init transactions array
        $newCsvData = [];

        // read each record
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rowNumber++;
            foreach($headers as $hdrIdx=>$header) {
                $newCsvData[$rowNumber][$header] = $row[$hdrIdx];
            }
        }

        // close the csv file
        fclose($handle);

        return $newCsvData;
    }   // end function readUploadCsv


    // get tolls from csv file
    public function readTollCsv() {

        // removes duplicates from tollData (no dup rcds from CSV file)
        function removeDupCSVTolls($tollData) {

            // toll data with duplicates removed
            $noDupsTollData = [];
            
            // for each toll records, only write it to the new noDupsTollData if it is not already there.
            foreach($tollData as $key=>$tollRecord) {

                // if $row is a duplicate, don't add it to tollData
                if(isUniqueCSVToll($tollRecord, $noDupsTollData)) {
                    $noDupsTollData[] = $tollRecord;
                } else {
                    error_log("This is a duplicate entry in the tolls CSV file, so it was not written to the tolls table:");
                    error_log(json_encode($tollRecord));
                    error_log("\n\n");
                }
            }

            return $noDupsTollData;
        }   // end of function removeDupCSVTolls

        // returns true if tollRecord is not already in noDupsTollData
        function isUniqueCSVToll($tollRecord, $noDupsTollData) {

            $isUnique = true;  // assume unique unless found otherwise
            foreach($noDupsTollData as $key=>$currToll) {
                if($currToll == $tollRecord) {
                    return false;   // it's already in the CSV file
                }
            }
            return $isUnique;   // Unique if no dups found
        }   // end of isUniqueCSVToll

        // returns true if tollRecord is not already in $tableTolls (from tolls table)
        function isUniqueTableToll($tollRecord, $tableTolls) {

            $isUnique = true;  // assume unique unless found otherwise
            foreach($tableTolls as $key=>$currToll) {
                // if date, time, transponder & amount match, it's a duplicate
                if(
                    $currToll->date == $tollRecord["Transaction Date"] &&
                    $currToll->time == $tollRecord["Transaction Time"] &&
                    substr($currToll->transponder, -8) == substr($tollRecord["Transponder/Plate"], -8) &&
                    $currToll->amount == $tollRecord["Outgoing"]
                ) {
                    return false;   // it's already in the tolls table
                }
            }

            return $isUnique;   // Unique if no dups found
        }   // end of isUniqueTableToll
        

        // removes duplicates from tollData that are already in the tolls table
        // considered a dupe if the car, date, time and amt match
        function removeDupTableTolls($tollData) {

            // need tolls from tolls table to compare.  Only need those in the date range in $tollData
            [$minDate, $maxDate] = getTollsDateRange($tollData);

            // get tolls from table
            $tableTolls = DB::table('tolls')
                ->select(
                    DB::raw('`Transaction Date` as date'),
                    DB::raw('`Transaction Time` as time'),
                    DB::raw('`Transponder/Plate` as transponder'),
                    DB::raw('`Outgoing` as amount')
                )
                ->where(DB::raw('`Transaction Date`'), '>=', $minDate)
                ->where(DB::raw('`Transaction Date`'), '<=', $maxDate)
                // ->where('`Transaction Date`', '>=', $minDate)
                ->get()->toArray();
            // error_log("\n\nTable tolls:  (type: " . gettype($tableTolls) . ")");
            // error_log(json_encode($tableTolls));
            // foreach($tableTolls as $key=>$tableToll) {
            //     error_log(" - " . $key . ":");
            //     error_log(" --- date: " . $tableToll->date);
            //     error_log(" --- time: " . $tableToll->time);
            //     error_log(" --- transponder: " . $tableToll->transponder);
            //     error_log(" --- amount: " . $tableToll->amount);
            // }

            // toll data with duplicates removed
            $noDupsTollData = [];

            // for each toll records, only write it to the new noDupsTollData if it is not in the tolls table ($tableTolls)
            foreach($tollData as $key=>$tollRecord) {

                // if $row is a duplicate, don't add it to tollData
                if(isUniqueTableToll($tollRecord, $tableTolls)) {
                    $noDupsTollData[] = $tollRecord;
                } else {
                    error_log("This is a duplicate entry in the tolls TABLE, so it was not written to the tolls table:");
                    error_log(json_encode($tollRecord));
                    error_log("\n\n");
                }
            }

            return $noDupsTollData;
        }   // end of function removeDupTableTolls
        

        // get min and max dates in tollData
        function getTollsDateRange($tollData) {

            // init min and max dates
            $minDate = $tollData[0]["Transaction Date"];
            $maxDate = $tollData[0]["Transaction Date"];

            // change min and max dates to the actual min and max dates in tollData
            foreach($tollData as $tollRecord) {
                if($tollRecord["Transaction Date"] < $minDate) $minDate = $tollRecord["Transaction Date"];
                if($tollRecord["Transaction Date"] > $maxDate) $maxDate = $tollRecord["Transaction Date"];
            }

            return [$minDate, $maxDate];
        }   // end of function getTollsDateRange

        // csv files are expected to be in the public/uploadFiles folder.
        $fullFilePath = public_path('uploadFiles/tolls.csv');

        // does the csv file exist?
        if (!file_exists($fullFilePath)) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' not found'], 404);
        }

        // open the file
        $handle = fopen($fullFilePath, 'r');

        // get header row
        $headers = fgetcsv($handle, 1000, ',');

        // make sure headers exist
        if($headers === FALSE) {
            return response()->json(['error' => 'File ' . $fullFilePath . ' has no headers'], 411);
        }

        // init transactions array
        $tollData = [];

        // read each record
        // keep these values for tolls table
        $dataHdrsToKeep = [
            "Agency",
            "Transponder/Plate",
            "Transaction Date",
            "Transaction Time",
            "Entry Plaza",
            "Exit Plaza",
            "Exit Lane",
            "Outgoing",
            "Car",
            "Trip"
        ];

        // build tollData array
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rowNumber++;
            foreach($headers as $hdrIdx=>$header) {
                if(in_array($header, $dataHdrsToKeep)) {
                    $tollData[$rowNumber][$header] = $row[$hdrIdx];
                }
            }
        }

        // close the csv file
        fclose($handle);

        // eliminate dups in csv file
        $tollData = removeDupCSVTolls($tollData);
        
        // eliminate tolls from tollData that are already in tolls table
        $tollData = removeDupTableTolls($tollData);

        return $tollData;
    }   // end function readTollCSV

    
    // tweak records for Checking to make import more helpful
    public function modifyCsvForChecking($newCsvData) {

        foreach($newCsvData as $idx=>$record) {
        
            // Combine "Amount Debit" or "Amount Credit" into Amount (won't be a number in both)
            if($record["Amount Debit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Debit"];
            else if ($record["Amount Credit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Credit"];
            else $newCsvData[$idx]["Amount"] = "0"; // to prevent an error

            // Amount Credit and Amount Debit are no longer needed
            unset($newCsvData[$idx]["Amount Credit"], $newCsvData[$idx]["Amount Debit"]);
        }

        return $newCsvData;
    }


    // tweak records for VISA to make import more helpful
    public function modifyCsvForVISA($newCsvData) {

        foreach($newCsvData as $idx=>$record) {

            // Combine "Amount Debit" or "Amount Credit" into Amount (won't be a number in both)
            if($record["Amount Debit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Debit"];
            else if ($record["Amount Credit"] != '') $newCsvData[$idx]["Amount"] = $record["Amount Credit"];
            else $newCsvData[$idx]["Amount"] = "0"; // to prevent an error

            // Amount Credit and Amount Debit are no longer needed
            unset($newCsvData[$idx]["Amount Credit"], $newCsvData[$idx]["Amount Debit"]);
        }

        return $newCsvData;
    }


    // Convert csv data to transaction records
    public function convertCsv($newCsvData, $accounts, $accountName, $key_dummies, $max_splits) {

        // handle each record for the specific account

        // need fields in the transactions table
        $transaction_fields = json_decode(file_get_contents(public_path('uploadFiles/transactionsFields.json')));

        // init newRecords
        $newRecords = [];

        // will need date info
        $dayOfMonth = date("d");
        $month = date("m");
        $year = date("Y");

        // get the day of the month for the last transaction on a statement
        //      defaults to the last day of the month
        $lastStmtDay = null;
        foreach ($accounts as $i=>$thisAccount) {
            if ($thisAccount->accountName == $accountName) {
                $lastStmtDay = $thisAccount->lastStmtDate;
                break;
            }
        }

        // get the last day of the month, if no lastStmtDate in accounts table
        if($lastStmtDay == null) {
            if(in_array($month, ['01', '03', '05', '07', '08', '10', '12'])) {
                $lastStmtDay = '31';
            } else if($month == '02') {
                $lastStmtDay = '29';
            } else {
                $lastStmtDay = '30';
            }
        }

        // get prefixes of toFrom's from banks to ignore
        $ignore = DB::table("tofromaliases")
            ->where('transToFrom', 'IGNORE')
            ->pluck('origToFrom');
                    
        // get mapping information (which csv fields map to which trans fields, including formulas)
        $mapping = DB::table("accounts")
            ->leftJoin("uploadmatch", "accounts.id", '=', "uploadmatch.account_id")
            ->select("csvField", "transField", "formulas")
            ->where("accountName", $accountName)
            ->get()->toArray();
        
        $current_split_idx = 0;

        // build transaction record from csv file record and write to the transactions table
        foreach($newCsvData as $i=>$transaction) {

            $fieldsLeft = [];   // transaction fields that have not yet been calculated
            $newRecord = new \stdClass();    // start with a fresh record to write to the db
            $splitRecords = []; // additional records for splits

            // all the fields are left, to begin with
            foreach($transaction_fields as $field) $fieldsLeft[] = $field;

            // set account
            $newRecord->account = $accountName;
            // account is done, remove from fieldsLeft
            removeElementByValue($fieldsLeft, 'account');

            // set fields as dictated by mapping (in uploadmatch table)
            foreach($mapping as $idx=>$map) {

                // if formula is null, it's a straight assignment
                if($map->formulas == null) {
                    $newRecord->{$map->transField} = $transaction[$map->csvField];
                    // field is done, remove from fieldsLeft
                    removeElementByValue($fieldsLeft, $map->transField);
                } else {
                    // if formula has a value, massage and evaluate it
                    //      the csvField indicates which element of the $transactions record,
                    //      so replace the csvField with the array reference
                    // see README.MD for allowed formulas
                    $formula = $map->formulas;  // string to be changed
                    $search = $map->csvField;   // string to look for
                    $replace = "\$transaction['".($map->csvField)."']"; // new string to replace
                    $formula = str_replace($map->csvField, $replace, $formula);

                    // handle concatenated fields
                    $isConcatPos = strpos($formula, ' + ');
                    if($isConcatPos !== false) {
                        $string2 = trim(substr($formula, $isConcatPos+2));
                        $replaceString2 = "\$transaction['".($string2)."']";
                        $formula = str_replace($string2, $replaceString2, $formula);
                        $formula = str_replace(" + ", " . ' ' . ", $formula);
                    }

                    // evaluate the formula to get the data needed
                    $newRecord->{$map->transField} = eval( "return $formula;");

                    // field is done, remove from fieldsLeft
                    removeElementByValue($fieldsLeft, $map->transField);
                }
                
                // the trans_date and clear_date need to be reformatted (mm/dd/yyyy to yyyy-mm-dd)
                if(strpos($map->transField, "_date") !== false) {
                     $newRecord->{$map->transField} = reformatDate( $newRecord->{$map->transField});
                }
            }

            // guess at statement date.  
            //  -- If it clears before the lastStmtDay, assume it's in the current month's statement
            //  -- Otherwise, make the statement date the next month.
            if($newRecord->clear_date <= ($year . "-" . $month . "-" . $lastStmtDay)) {
                $newRecord->stmtDate = date("y") . "-" . date("M");
            } else {
                // if Dec, add 1 to year when adding one to the month
                if($month == 12) {
                    $stmtDateYear = date("y") + 1;
                } else {
                    $stmtDateYear = date("y");
                }
                $newRecord->stmtDate = $stmtDateYear . "-" . date("M", mktime(0,0,0,$month+1,$dayOfMonth,$year));
            }        

            // stmtDate is done, remove from fieldsLeft
            removeElementByValue($fieldsLeft, "stmtDate");

            // remove strings to ignore from transaction toFrom
            $lc_toFrom = strtolower($newRecord->toFrom);
            foreach ($ignore as $ignoreString) {
                $lc_toFrom = preg_replace('/\b' . preg_quote(strtolower($ignoreString), '/') . '\b/', '', $lc_toFrom);
                $lc_toFrom = trim($lc_toFrom);
            }
            $newRecord->toFrom = $lc_toFrom;

            // handle different length origToFrom values
            $toFrom = $newRecord->toFrom;
            $toFromAlias = DB::table("tofromaliases")
                ->where('origToFrom', '=', DB::raw('LEFT(?, LENGTH(origToFrom))'))
                ->setBindings([$toFrom])
                // ->dumpRawSql()
                ->first();

            // handle default categories, notes, tracking, and splits
            if($toFromAlias) {
                $newRecord->toFrom = $toFromAlias->transToFrom;  // might not have been caught before; if origToFrom in table < 11 chars
                $category = $toFromAlias->category;
                $newRecord->category = $category ?? null;
                
                // handle extraDefaults
                $extraDefaults = $toFromAlias->extraDefaults;

                if($extraDefaults) {
                    $extraDefaultsArray = json_decode($extraDefaults, true);

                    // Handle notes
                    if (isset($extraDefaultsArray['notes'])) {
                        $newRecord->notes = $extraDefaultsArray['notes'];
                    }
                    
                    // Handle tracking
                    //  -- just beginning - handle multiple tracking (one for each split)
                    $multipleTracking = false;
                    // is there default tracking?
                    if (isset($extraDefaultsArray['tracking'])) {
                        // if it's an array, there may be more than one tracking element
                        if(is_array($extraDefaultsArray['tracking'])) {
                            // use the first tracking in the first new record
                            $newRecord->tracking = $extraDefaultsArray['tracking'][0];
                            // if the array has more than one element, store the extra tracking elements in $multipleTracking
                            //      to write to cloned split transaction records.
                            if(count($extraDefaultsArray['tracking']) > 1) {
                                $multipleTracking = array_slice($extraDefaultsArray['tracking'], 1);
                            }

                        // if tracking is just text, that will be the tracking for the original and any split transactions.
                        } else {
                            // multipleTracking will be false in this case
                            $newRecord->tracking = $extraDefaultsArray['tracking'];
                        }
                    }

                    // Handle splits
                    if (isset($extraDefaultsArray['splits'])) {

                        if(is_numeric($extraDefaultsArray['splits'])) {
                            $typeSplit = "number";
                            $numberSplits = $extraDefaultsArray['splits'] + 1;      // +1 for original record
                        } else {
                            $typeSplit = "categories";
                            $numberSplits = count($extraDefaultsArray['splits']) + 1;      // +1 for original record
                        }
                        
                        // adjust newRecord
                        $origAmount = $newRecord->amount;
                        $splitAmount = round($origAmount/$numberSplits, 4); // to 4 decimal places; fix manually if needed
                        $newRecord->amount = $splitAmount;
                        $newRecord->total_amt = $origAmount;
                        $newRecord->total_key = $key_dummies[$current_split_idx];
                        $current_split_idx++;
                        if($current_split_idx >= $max_splits) { 
                            error_log("\n\n\n  TOO MANY SPLIT TRANSACTIONS!!  ");
                        }
                        if($newRecord->category == "MikeSpending") {
                            $newRecord->amtMike = $splitAmount;
                            $newRecord->amtMaura = 0;
                        } else if($newRecord->category == "MauraSpending") {
                            $newRecord->amtMaura = $splitAmount;
                            $newRecord->amtMike = 0;
                        } else {
                            $newRecord->amtMaura = round($splitAmount/2, 4);
                            $newRecord->amtMike = round($splitAmount/2, 4);
                        }

                        // create splits from copy of newRecord
                        for( $numSplit = 0; $numSplit < $numberSplits-1; $numSplit++) {

                            // make a full copy
                            $splitRecord = unserialize(serialize($newRecord));

                            // modify as needed
                            if($typeSplit == "categories") $splitRecord->category = $extraDefaultsArray['splits'][$numSplit];
                            else $splitRecord->category = null;

                            if($splitRecord->category == "MikeSpending") {
                                $splitRecord->amtMike = $splitAmount;
                                $splitRecord->amtMaura = 0;
                            } else if($splitRecord->category == "MauraSpending") {
                                $splitRecord->amtMaura = $splitAmount;
                                $splitRecord->amtMike = 0;
                            } else {
                                $splitRecord->amtMaura = round($splitAmount/2, 4);
                                $splitRecord->amtMike = round($splitAmount/2, 4);
                            }

                            // change default tracking, if there were multiple ones
                            if($multipleTracking && isset($multipleTracking[$numSplit])) {
                                $splitRecord->tracking = $multipleTracking[$numSplit];
                            }

                            // append to array of split records
                            $splitRecords[] = $splitRecord;
                        }
                    }

                }
            }

            // add this newRecord to newRecords
            $newRecords[] = $newRecord;
            // add each splitRecord to newRecords
            foreach($splitRecords as $splitRecord) {
                $newRecords[] = $splitRecord;
            }
        }

        return $newRecords;
    }   // end function convertCsv


    // find and question possible duplicates
    // Possible duplicates have:
    //      same account
    //      same toFrom (To Do: check aliases)
    //      trans_date within 2 days
    //      amount in new transaction matches amount in existing transaction
    //              or total_amt in existing transaction
    public function findDuplicates($newRecords) {

        foreach($newRecords as $rcdIdx=>$record) {

            // check dates within two days of trans_date
            $trans_date = Carbon::parse($record->trans_date);
            $orig_date = $trans_date->format('Y-m-d');
            
            // get one & 2 days before trans_date
            $previousDay = $trans_date->subDays(1)->format('Y-m-d');
            // subract another day
            $twoDaysBefore = $trans_date->subDays(1)->format('Y-m-d');
            
            // get one and 2 days after trans_date
            // add 3 to twoDaysBefore
            $nextDay = $trans_date->addDays(3)->format('Y-m-d');
            // add one more day
            $twoDaysAfter = $trans_date->addDays(1)->format('Y-m-d');
            
            // original trans_date is two days before "twoDaysAfter"
            $trans = $trans_date->addDays(1)->format('Y-m-d');

            // formatted dates
            $formattedDates = [
                $twoDaysBefore,
                $previousDay,
                $orig_date,
                $nextDay,
                $twoDaysAfter
            ];

            $dupsMaybe = DB::table('transactions')
                ->where('account', $record->account)
                ->where('toFrom', $record->toFrom )
                ->whereIn('trans_date', $formattedDates)
                ->where(function ($query) use ($record) {
                    $query->where('amount', $record->amount)
                        ->orWhere('total_amt', $record->amount);
                })
                ->get()->toArray();
                           
            // add element to indicate if this might be a duplicate transaction
            // if(count($dupsMaybe) > 0) $newRecords[$rcdIdx]['dupMaybe'] = true;
            if(count($dupsMaybe) > 0) $newRecords[$rcdIdx]->dupMaybe = true;
            // else $newRecords[$rcdIdx]['dupMaybe'] = false;
            else $newRecords[$rcdIdx]->dupMaybe = false;
            
            // error_log("\ndupsMaybe: ");
            // if(count($dupsMaybe) > 0) {
            //     foreach($dupsMaybe as $dup) error_log(json_encode($dup));
            // } else {
            //     error_log("none");
            // }
            // error_log("--- for " . $record['account'] . "; " . $record['toFrom'] . "; " . $record['trans_date'] . "; " . $record['amount']);

        }
        
        return $newRecords;
    }


    // write transactions to database; adds ids to $records
    public function writeNewRecordsToTransactions($records) {

        $recordsWithIds = [];  // ids assigned when record written

        foreach($records as $record) {
            // write the transaction to the database.
            try {
                $dupMaybe = $record->dupMaybe ?? false;
                $split_total = $record->split_total ?? null;
                unset($record->dupMaybe);
                unset($record->split_total);

                $result = DB::table('transactions')->insertGetId((array)$record);
                $record->id = $result;

                $record->dupMaybe = $dupMaybe;
                $record->split_total = $split_total;
                $recordsWithIds[] = $record;     // for the blade to show uploaded transactions with id

            } catch (\Exception $e) {
                \Log::error('Error adding record to the transactions table: ' . $e->getMessage() . "\nRecord: " . json_encode($record));
                throw $e;
            }
        }

        return $recordsWithIds;
    }


    // get budget for the full year for each category and year
    function findTotalBudget($budgets, $year, $category) {
        foreach ($budgets as $budget) {
            if ($budget->year == $year && $budget->category == $category) {
                return floatval($budget->total_budget);
            }
        }
        
        // If no match is found, return 0
        return 0;
    }


    // get total spent for each category
    function findSpent($spentTotals, $category) {
        foreach ($spentTotals as $spentTotal) {
            if ($spentTotal->category == $category) {
                return floatval($spentTotal->spent);
            }
        }
        
        // If no match is found, return 0
        return 0;
    }


    // List of accounts with balances (cleared & register), Last Balanced, and button to see transactions for that account
    //      includes line "all" for all transactions
    public function index($acctsMsg = null) {
        
        // get all account names & ids
        $results = DB::table('accounts')
            ->select("id", "accountName")
            ->where("type", "trans")
            ->get()->toArray();
        $accountNames = array_column($results, 'accountName');
        $accountIds = array_column($results, 'id');

        $accounts = DB::table('transactions')
            ->select(
                'account',
                DB::raw('SUM(CASE WHEN clear_date IS NOT NULL THEN amount ELSE 0 END) AS cleared'),
                DB::raw('SUM(amount) AS register'),
                DB::raw('MAX(lastBalanced) AS max_last_balanced')
            )
            ->whereIn(
                'account',
                $accountNames
            )
            ->groupBy('account')
            ->get()
            ->toArray();

        // order accounts alphabetically
        usort($accounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        $all = new \stdClass();
        $all->account = "all";
        $all->cleared = 0;
        $all->register = 0;
        $all->max_last_balanced = "";
        foreach($accounts as $account) {
            $all->cleared += $account->cleared;
            $all->register += $account->register;
            $all->max_last_balanced = max($all->max_last_balanced, $account->max_last_balanced);
        }

        // add too array to be written to the page
        $accounts[] = $all;

        // format $ amounts  for each account (& all)
        foreach($accounts as $acctIdx=>$account) {
            $accounts[$acctIdx]->cleared = number_format($account->cleared, 2);
            $accounts[$acctIdx]->register = number_format($account->register, 2);
        }

        return view('accounts', ['accounts' => $accounts, 'acctsMsg' => $acctsMsg]);
    }

    function calcSplitTotals($transactions) {

        // get splits that might not be in transactions & ids
        $splitKeys = array_column($transactions, 'total_keys');
        $ids = array_column($transactions, 'id');

        $extraSplitTransactions = DB::table("transactions")
            ->whereIn("total_key", $splitKeys)
            ->whereNotIn("id", $ids)
            ->get()->toArray();

        if(count($extraSplitTransactions) != 0) {
            error_log("\n\n\nNot ALL splits are in this timeframe!!!\n\n\n");
        }

        // make sure all transactions have a total_key
        foreach($transactions as $trxIdx=>$transaction) {
            if(!isset($transaction->total_key)) $transactions[$trxIdx]->total_key = null;
        }

        // Group transactions by total_key and sum amounts
        if($ids != []) {
            $splitTotals = array_reduce($transactions, function($splitSum, $item) {
                if ($item->total_key) {
                    $splitSum[$item->total_key] = ($splitSum[$item->total_key] ?? 0) + floatval($item->amount);
                }
                return $splitSum;
            }, []);
        } else {
            $splitTotals = [];
        }

        if(count($splitTotals) > 0) {
            foreach($transactions as $transaction) {
                $splitTotal = $splitTotals[$transaction->total_key] ?? null;
                $transaction->split_total = round($splitTotal, 4);
            }
        } else {
            foreach($transactions as $transaction) {
                $transaction->split_total = "n/a";
            }
        }
        
        return $transactions;
    }   // end of function calcSplitTotals


    // for route /accounts/{accountName}/{beginDate}/{endDate}
    public function transactions($accountName, $beginDate = 'null', $endDate = 'null',  $clearedBalance = NULL, $registerBalance = NULL, $lastBalanced = NULL) {

        // begin & end date default to a string null to keep the place in the url
        if($beginDate == 'null') $beginDate = null;
        if($endDate == 'null') $endDate = null;

        // set beginDate and endDate if not passed in (or null)
        if($beginDate == null || $endDate == null) {
            [$beginDate, $endDate] = $this->setDefaultBeginEndDates($beginDate, $endDate);
        }

        // get the accounts information for all accounts
        $accounts = DB::table("accounts")
            ->get()->toArray();
        // error_log("\naccounts: ");
        // foreach($accounts as $thisOne) error_log(" - " . json_encode($thisOne));

        // get all previously used toFrom values
        $toFroms = DB::table("transactions")
            ->distinct()->get("toFrom")->toArray();
        $toFroms = array_column($toFroms, 'toFrom');
        $toFroms = str_replace(" ", "%20", json_encode($toFroms));

        // get tofromaliases (auto converts what the "bank" uses to what's in the database)
        $tofromaliases = DB::table("tofromaliases")
            ->get()->toArray();
        $tofromaliases = str_replace(" ", "%20", json_encode($tofromaliases));
        // error_log("\ntofromaliases:");
        // foreach($tofromaliases as $thisOne) error_log(" - " . json_encode($thisOne));

        // get all the defined account names
        $accountNames = array_column($accounts, 'accountName');
        $accountIds = array_column($accounts, 'id');
        // error_log("accountNames: " . json_encode($accountNames));
        // error_log("accountIds: " . json_encode($accountIds));

        // cut-off dates for a statement period, if not the end of the month
        $allLastStmtDates = array_column($accounts, 'lastStmtDate');

        $lastStmtDates = [];
        foreach($accounts as $accountIdx=>$account) {
            if($allLastStmtDates[$accountIdx] !== null) {
                $lastStmtDates[] = [
                    'accountName' => $accountNames[$accountIdx],
                    'lastStmtDate' => $allLastStmtDates[$accountIdx]
                ];
            }
        }

        // if accountName not in accounts and is not 'all', it's not a valid accountName
        if(!in_array($accountName, $accountNames) && $accountName != 'all') {
            return response()->json(['error' => $accountName . ' is not a defined account'], 412);
        }

        // get all defined categories
        $categories = DB::table("transactions")
            ->distinct()->get("category")->toArray();
        $categories = array_column($categories, 'category');
        $categories = str_replace(" ", "%20", json_encode($categories));

        // get all used tracking values
        $trackings = DB::table("transactions")
            ->distinct()->get("tracking")->toArray();
        $trackings = array_column($trackings, 'tracking');
        $trackings = str_replace(" ", "%20", json_encode($trackings));

        // get all bucket names
        $buckets = DB::table("transactions")
            ->whereNotNull('bucket')
            ->distinct()->get("bucket")->toArray();
        $buckets = array_column($buckets, 'bucket');
        $buckets = str_replace(" ", "%20", json_encode($buckets));

        // get the outstanding transactions that have not cleared yet for the requested account ('all' for all transactions) and time period
        $outstandingTransactions = DB::table('transactions')
            ->when($accountName != 'all', function ($query) use ($accountName) {
                return $query->where('account', $accountName);
            })
            ->whereNull('clear_date')
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')
            ->get()
            ->toArray();

        // get the cleared transactions for the requested account ('all' for all transactions) and time period
        $clearedTransactions = DB::table('transactions')
            ->when($accountName != 'all', function ($query) use ($accountName) {
                return $query->where('account', $accountName);
            })
            ->where('trans_date', '>=', $beginDate)
            ->where('trans_date', '<=', $endDate)
            ->whereNotNull('clear_date')
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')            ->get()
            ->toArray();

        // combine transactions
        $transactions = array_merge($outstandingTransactions, $clearedTransactions);

        // calc split transaction totals
        $transactions = $this->calcSplitTotals($transactions);

        // Get today's date (for next few queries)
        $thisMonth = date('m');
        $thisYear = date('Y');
        // error_log("thisMonth: " . $thisMonth . "; thisYear: " . $thisYear);
        $firstDay = $thisYear . "-01-01";

        // get amount spent for this category this year
        $spentTotals = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as spent')
            ->where('trans_date', '>=', $firstDay)
            ->groupBy('category')
            ->get()
            ->toArray();

        // get year-to-month budgets by category (budget this year up to and including this month)
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // months to use to get YTM budget
        $selectedMonths = array_slice($months, 0, $thisMonth);
        
        $ytmBudgets = DB::table('budget')
            ->selectRaw("year, category, " . implode(' + ', array_map(fn($m) => "COALESCE($m, 0)", $selectedMonths)) . " as total_budget")
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()->toArray();
        // error_log("new var: \n" . json_encode($ytmBudgets));

        // get full year budgets by category
        $yearBudgets = DB::table('budget')
            ->select('year', 'category', 'total as total_budget')
            ->where('year', $thisYear)
            ->groupBy()
            ->get()
            ->toArray();
        // error_log("new yearBudgets: \n" . json_encode($yearBudgets));

        // add ytd spent, budget through current month, full year budget to transactions variable for each transaction
        // and fill in accountId
        foreach($transactions as $transaction) {
            $ytmBudget = $this->findTotalBudget($ytmBudgets, $thisYear, $transaction->category);
            $thisYearBudget = $this->findTotalBudget($yearBudgets, $thisYear, $transaction->category);
            $spent = $this->findSpent($spentTotals, $transaction->category);
            $transaction->ytmBudget = $ytmBudget;
            $transaction->yearBudget = $thisYearBudget;
            $transaction->spent = $spent;

            // fill in accountId
            $accountIdx = array_search($transaction->account, $accountNames);
            $transaction->accountId = $accountIds[$accountIdx];
        }

        // get cars
        $cars = DB::table('carcostdetails')
            ->where('key', 'Purchase')
            ->pluck('car');

        return view('transactions', ['accountName' => $accountName, 'newTransactions' => [], 'transactions' => $transactions, 'beginDate' => $beginDate, 'endDate' => $endDate, 'accountNames' => $accountNames, 'accountIds' => $accountIds, 'lastStmtDates' => $lastStmtDates, 'toFroms' => $toFroms, 'tofromaliases' => $tofromaliases, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => false, 'clearedBalance' => $clearedBalance, 'registerBalance' => $registerBalance, 'lastBalanced' => $lastBalanced, 'cars' => $cars]);
    }


    // for route /accounts/{$accountName}/balances
    // sets the lastBalanced column to now for the given accountName
    public function balances($accountName) {

        // timestamp for now
        $now = time();
        $now = date('Y-m-d H:i:s', $now);

        try {
            DB::table('transactions')
                ->where('account', $accountName)
                ->whereNull('lastBalanced')
                ->whereNotNull('clear_date')
                ->update(['lastBalanced' => $now]);
            return redirect()->route('accounts')->with("acctsMsg", "Last balances updated.");

        } catch (\Exception $e) {
            \Log::error('Error updating lastBalanced columns in transactions table: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating lastBalanced columns'], 500);
        }

    }


    // returns the subarray with the given key,
    // or false if not found
    public function findArray($array, $key) {
        foreach ($array as $subarray) {
            if ($subarray[0] === $key) {
                return $subarray;
            }
        }
        return false;
    }


    // reads the csv file, massages, and writes to transactions table
    public function upload($accountName) {

        // convert mm/dd/yyyy to yyyy-mm-dd
        function reformatDate($date) {
            $dateParts = explode("/", $date);
            return $dateParts[2] . "-" . $dateParts[0] . "-" . $dateParts[1];
        }
        
        // drop element from an array given the value to be dropped
        function removeElementByValue(&$array, $value) {
            $key = array_search($value, $array);
            if ($key !== false) {
                unset($array[$key]);
            }
        }

       
        // set default beginDate and endDate
        [$beginDate, $endDate] = $this->setDefaultBeginEndDates();

        // get the accounts information for all accounts
        $accounts = DB::table("accounts")
            ->get()->toArray();
        // error_log("\naccounts: ");
        // foreach($accounts as $thisOne) error_log(" - " . json_encode($thisOne));
        $accountIds = array_column($accounts, 'id');

        // get all previously used toFrom values
        $toFroms = DB::table("transactions")
            ->distinct()->get("toFrom")->toArray();
        $toFroms = array_column($toFroms, 'toFrom');
        $toFroms = str_replace(" ", "%20", json_encode($toFroms));
        
        // get tofromaliases (auto converts what the "bank" uses to what's in the database)
        $tofromaliases = DB::table("tofromaliases")
            ->get()->toArray();
        $tofromaliases = str_replace(" ", "%20", json_encode($tofromaliases));

        // get all the defined account names
        $accountNames = array_column($accounts, 'accountName');

        // if accountName not in accounts, it's not a valid accountName
        if(!in_array($accountName, $accountNames)) {
            return response()->json(['error' => $accountName . ' is not a defined account'], 412);
        }

        // cut-off dates for a statement period, if not the end of the month
        $allLastStmtDates = array_column($accounts, 'lastStmtDate');

        $lastStmtDates = [];
        foreach($accounts as $accountIdx=>$account) {
            if($allLastStmtDates[$accountIdx] !== null) {
                $lastStmtDates[] = [
                    'accountName' => $accountNames[$accountIdx],
                    'lastStmtDate' => $allLastStmtDates[$accountIdx]
                ];
            }
        }

        // get all defined categories
        $categories = DB::table("transactions")
            ->distinct()->get("category")->toArray();
        $categories = array_column($categories, 'category');
        $categories = str_replace(" ", "%20", json_encode($categories));

        // get all used tracking values
        $trackings = DB::table("transactions")
            ->distinct()->get("tracking")->toArray();
        $trackings = array_column($trackings, 'tracking');
        $trackings = str_replace(" ", "%20", json_encode($trackings));

        // get all bucket names
        $buckets = DB::table("transactions")
            ->whereNotNull('bucket')
            ->distinct()->get("bucket")->toArray();
        $buckets = array_column($buckets, 'bucket');
        $buckets = str_replace(" ", "%20", json_encode($buckets));

        // get recent existing transactions (to visually check for duplicate transactions)
        $transactions = DB::table("transactions")
            ->where("account", $accountName)
            ->where("trans_date", ">=", $beginDate)
            ->where("trans_date", "<=", $endDate)
            ->orderBy('trans_date', 'desc')
            ->orderBy('total_key', 'desc')
            ->orderBy("toFrom")
            ->get()
            ->toArray();
    
        // calc split transaction totals
        $transactions = $this->calcSplitTotals($transactions);

        // Get today's date (for next few queries)
        $thisMonth = date('m');
        $thisYear = date('Y');
        $firstDay = $thisYear . "-01-01";

        // get amount spent for this category this year
        $spentTotals = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as spent')
            ->where('trans_date', '>=', $firstDay)
            ->groupBy('category')
            ->get()
            ->toArray();

        // get year-to-month budgets by category (budget this year up to and including this month)
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // months to sum budgets to get YTM budget
        $selectedMonths = array_slice($months, 0, $thisMonth);
        
        $ytmBudgets = DB::table('budget')
            ->selectRaw("year, category, " . implode(' + ', array_map(fn($m) => "COALESCE($m, 0)", $selectedMonths)) . " as total_budget")
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()->toArray();
        // error_log("new var: \n" . json_encode($ytmBudgets));
    
        // get full year budgets by category
        $yearBudgets = DB::table('budget')
            ->select('year', 'category', 'total as total_budget')
            ->where('year', $thisYear)
            ->groupBy()
            ->get()
            ->toArray();
        // error_log("new yearBudgets: \n" . json_encode($yearBudgets));

        // add ytd spent, budget through current month, full year budget to transactions variable for each transaction
        foreach($transactions as $transaction) {
            $ytmBudget = $this->findTotalBudget($ytmBudgets, $thisYear, $transaction->category);
            $thisYearBudget = $this->findTotalBudget($yearBudgets, $thisYear, $transaction->category);
            $spent = $this->findSpent($spentTotals, $transaction->category);
            $transaction->ytmBudget = $ytmBudget;
            $transaction->yearBudget = $thisYearBudget;
            $transaction->spent = $spent;
        }

        // READ transactions from csv file
        $newCsvData = $this->readUploadCsv($accountName);
      
        // If Checking, modify csv for upload
        if($accountName == "Checking") $newCsvData = $this->modifyCsvForChecking($newCsvData);
        // If VISA, modify csv for upload
        if($accountName == "VISA") $newCsvData = $this->modifyCsvForVISA($newCsvData);

        // will be replaced when id's are determined
        $key_dummies = ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff', 'ggg', 'hhh', 'iii', 'jjj'];
        $max_splits = count($key_dummies);

        // Convert csv data to transaction records
        $newRecords = $this->convertCsv($newCsvData, $accounts, $accountName, $key_dummies, $max_splits);

        // add split totals to newRecords
        // need record ids, first.
        // get max id in transactions table
        $maxId = DB::table('transactions')
            ->max('id');

        $nextId = $maxId + 1;
        $dummyToRealTotalKeyMap = [];
        $current_dummy_idx = 0;

        // assign ids, and fill in total_keys where needed
        foreach($newRecords as $newIdx=>$newRecord) {
            $newRecords[$newIdx]->id = $nextId;

            // if total_key is not a number, it is a dummy and needs to be replaced.
            if(isset($newRecord->total_key) && !is_numeric($newRecord->total_key)) {

                // is this a new mapping?
                $existingMap = $this->findArray($dummyToRealTotalKeyMap, $newRecord->total_key);

                // if new, mape a new map array
                // and set total_key to the nextId
                if($existingMap === false) {
                    $map = [$newRecord->total_key, $nextId];
                    $dummyToRealTotalKeyMap[] = $map;
                    $newRecords[$newIdx]->total_key = $nextId;
                } else {
                    // set total_key from map
                    $newRecords[$newIdx]->total_key = $existingMap[1];
                }
            }

            $nextId++;
        }
        
        // fill in split totals for new records
        $newRecords = $this->calcSplitTotals($newRecords);

        // Look for possible duplicate transactions
        $newRecords = $this->findDuplicates($newRecords);

        // Write new records to transactions table
        $newTransactions = $this->writeNewRecordsToTransactions($newRecords);
        foreach($newTransactions as $trxIdx=>$newTransaction) {
            $newTransactions[$trxIdx] = (array)$newTransaction; // for page (expecting arrays)
        }

        // get cars
        $cars = DB::table('carcostdetails')
            ->where('key', 'Purchase')
            ->pluck('car');

        // TO DO:  order transactions by trans_date descending, toFrom ascending

        return view('transactions', ['accountName' => $accountName, 'newTransactions' => $newTransactions, 'transactions' => $transactions, 'accountNames' => $accountNames, 'accountIds' => $accountIds, 'lastStmtDates' => $lastStmtDates, 'tofromaliases' => $tofromaliases, 'toFroms' => $toFroms, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => true, 'beginDate' => $beginDate, 'endDate' => $endDate, 'clearedBalance' => '', 'registerBalance' => '', 'lastBalanced' => '', 'cars' => $cars]);
    }   // end of function upload


    // reads the csv file, massages, and writes to tolls table
    // tolls to upload should be in public/uploadFiles/tolls
    public function uploadtolls() {

        // READ toll records from csv file (only keeping what is needed)
        $tollRcds = $this->readTollCsv();

        // Write toll records to tolls table
        $result = DB::table('tolls')
                ->insert($tollRcds);

        if($result) {
            return response()->json([
                'message' => 'Tolls successfully written to tolls table. ' . $result,
                'status' => 'success'
            ]);
        } else {
            return response()->json([
                'message' => 'Unexpected error writing tolls to tolls table.',
                'status' => 'error'
            ]);
        }
    }   // end of function uploadtolls


    // sums tolls in tolls table for given trip & returns the sum of the tolls
    public function tallytolls(Request $request) {

        // get trip passed in
        $data = json_decode($request->getContent(), true);
        $trip = $data['trip'];

        // get sum of tolls (Outgoing) from tolls table for this trip
        $tolls = DB::table('tolls')
                ->where("trip", $trip)
                ->get()->toArray();

        // from each toll record, sum total tolls; pass back each toll record for user to verify
        $sumTolls = 0;
        $tollRcds = [];
        foreach($tolls as $toll) {
            $sumTolls += $toll->Outgoing;
            $tollRcds[] = [
                $toll->{'Transaction Date'},
                substr($toll->{'Transaction Time'}, 0, 5),
                $toll->{'Exit Plaza'},
                $toll->{'Exit Lane'},
                $toll->Outgoing
            ];
        }

        // sort tolls by date and time, ascending
        usort($tollRcds, fn($a, $b) => [
            $a[0], // Transaction Date (ascending)
            substr($a[1], 0, 5), // Transaction Time (ascending)
        ] <=> [
            $b[0], // Transaction Date (ascending)
            substr($b[1], 0, 5) // Transaction Time (ascending)
        ]);

        // round the tolls total
        $sumTolls = round($sumTolls, 2);

        // if it's a number, return it
        if(is_numeric($sumTolls)) {
            return response()->json([
                'message' => 'Sum of tolls successfully retrieved from tolls table.',
                'status' => 'success',
                'tolls' => $sumTolls,
                'tollRcds' => json_encode($tollRcds)
            ]);
        // otherwise, return error
        } else {
            return response()->json([
                'message' => 'Unexpected error getting tolls sum from tolls table.',
                'status' => 'error'
            ]);
        }
    }   // end of function tallytolls


    // delete a transaction by id
    public function delete($id)
    {
        try {
            $response = DB::table('transactions')
                ->where("id", $id)
                ->delete();

            if($response == 1) {
                return response()->json([
                    'message' => 'Number records deleted: ' . $response,
                    'status' => 'success'
                ]);
            } else {
                return response()->json([
                    'message' => 'Unexpected number of records deleted: ' . $response,
                    'status' => 'error'
                ]);
            }

        } catch(\Exception $e) {
            error_log("\nProblem deleting transaction for id: " . $id);
            error_log(json_encode(['exception' => $e]));
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
        
    }

    // update a transaction
    public function update(Request $request)
    {
        try {
            // get transaction to update from payload
            $data = json_decode($request->getContent(), true);
            $transaction = $data['newTransaction'];

            // remove url encoding
            $transaction = urldecode($transaction);

            // put it back as an object (from json)
            $transaction = json_decode($transaction);
            $id = $transaction->id;

            // set fields to be updated
            $dataToUpdate = [
                'trans_date' => $transaction->trans_date,
                'clear_date' => $transaction->clear_date,
                'toFrom' => $transaction->toFrom,
                'amount' => $transaction->amount,
                'amtMike' => $transaction->amtMike,
                'amtMaura' => $transaction->amtMaura,
                'method' => $transaction->method,
                'category' => $transaction->category,
                'tracking' => $transaction->tracking,
                'stmtDate' => $transaction->stmtDate,
                'total_amt' => $transaction->total_amt,
                'total_key' => $transaction->total_key,
                'notes' => $transaction->notes
            ];

            $response = DB::table("transactions")
                ->where('id', $id)
                ->update($dataToUpdate);

            return response()->json([
                'message' => "Transaction updated successfully"
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            logger()->error("Error updating transaction: " . $e->getMessage());
            error_log("Error updating transaction: " . $e->getMessage());
           
            // Re-throw the exception
            return response()->json([
                'error' => 'Failed to update transaction'
            ], 500);
        }
            
    }


    // update a investment account balances
    public function updateInvBalances(Request $request)
    {
        // reformats $date as yy-Mon (where Mon is a 3 char month abbrev)
        function formatStmtDate($date) {
            $dateTime = new \DateTime($date);
            $year = $dateTime->format('y');

            $fullMonthName = $dateTime->format('F');
            $abbrevMon = substr($fullMonthName, 0, 3);

            return "$year-$abbrevMon";
        }

        try {
            // get account info to update from payload
            $data = json_decode($request->getContent(), true);
            $newBalancesInfo = $data['newBalancesInfo'];

            // get today's date for the note in the database
            date_default_timezone_set('America/New_York');
            $today = date('Y-m-d');
            $note = "on " . $today;

            // set fields to be inserted that are the same for each record
            $newBalanceRcd = [
                'toFrom' => "Value",
                'category' => "Value",
                'notes' => $note,
                'lastBalanced' => $today
            ];

            // init records that will be inserted
            $recordsToInsert = [];

            // change data to be inserted for each element in newBalanceInfo
            foreach($newBalancesInfo as $idx=>$newBalanceInfo) {

                $newBalanceRcd['account'] = $newBalanceInfo['account'];
                $newBalanceRcd['trans_date'] = $newBalanceInfo['trans_date'];
                $newBalanceRcd['clear_date'] = $today;
                $newBalanceRcd['amount'] = $newBalanceInfo['amount'];
                $newBalanceRcd['amtMike'] = $newBalanceInfo['amount'] / 2;
                $newBalanceRcd['amtMaura'] = $newBalanceInfo['amount'] / 2;
                $stmtDate = formatStmtDate( $newBalanceInfo['trans_date']);
                $newBalanceRcd['stmtDate'] = $stmtDate;

                // build array of records to be inserted
                $recordsToInsert[] = $newBalanceRcd;
            }

            // insert the records
            $response = DB::table("transactions")
                ->insert($recordsToInsert);

            return response()->json([
                'message' => "Investment balances updated successfully"
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            logger()->error("Error inserting records to update investment balances: " . $e->getMessage());
            error_log("Error inserting records to update investment balances: " . $e->getMessage());
            
            // Re-throw the exception
            return response()->json([
                'error' => 'Failed to update investment balances'
            ], 500);
        }
            
    }   // end of function updateInvBalances


    // insert a new toFromAlias record
    public function insertAlias($origToFrom, $newValue) 
    {

        $origToFrom = urldecode($origToFrom);
        $newValue = urldecode($newValue);

        try {
            
            $response = DB::table('tofromaliases')
                ->insert([
                    'origToFrom' => $origToFrom,
                    'transToFrom' => $newValue,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                // 'recordId' => $record->id
            ], 200);
        } catch(\Exception $e) {
            error_log("\nProblem inserting tofromaliases record for origToFrom: " . $origToFrom . " and transToFrom: " . $newValue);
            error_log(json_encode(['exception' => $e]));
            \Log::error('Error inserting tofromaliases record.  ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        }

        // return response()->json([
        //     'message' => 'Record inserted successfully',
        //     'status' => 'success'
        // ]);
    }


    // insert a new transaction record
    public function insert(Request $request) 
    {
        $data = json_decode($request->getContent(), true);
        $transaction = urldecode($data['newTransaction']);
        $transaction = json_decode($transaction);

        // $transaction needs to be array, not object
        $transaction = (array)$transaction;

        // change null numeric values to 0
        foreach(["amount", "amtMike", "amtMaura"] as $field) {
            if($transaction[$field] == null) $transaction[$field] = 0;
        }

        try {
            // insert the transaction
            $response = DB::table('transactions')
                ->insert($transaction);
            $recordId = DB::getPdo()->lastInsertId();
            
            // if total_key is not numeric (it's a placeholder) and not null, update the total_key for all transactions with total_key matching current record
            if(!is_numeric($transaction['total_key']) && $transaction['total_key'] != null) {
                // the new total_key is the id of the newly saved transaction
                $newTotalKey = $recordId;
                $response = DB::table('transactions')
                    ->where('total_key', $transaction['total_key'])
                    ->update(['total_key' => $newTotalKey]);
            } else {
                $newTotalKey = false;
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction inserted successfully',
                'newTotalKey' => $newTotalKey,
                'recordId' => $recordId
            ], 200);
        } catch(\Exception $e) {
            error_log("\nProblem inserting transaction record");
            error_log(json_encode(['exception' => $e->getMessage()]));
            \Log::error('Error inserting transaction record.  ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        }
    }


    // get default values for a given toFrom
    public function getDefaults($account, $toFrom): JsonResponse
    {

        try {
            $accountId = DB::table('accounts')
                ->where('accountName', $account)
                ->pluck('id');
            $accountId = $accountId[0];
            $lowerToFrom = strtolower($toFrom);

            $defaults = DB::table('tofromaliases')
                ->where('account_id', $accountId)
                ->whereRaw("LOWER(transToFrom) = ?", [$lowerToFrom])
                ->first();

            // if none found, look in origToFrom
            if($defaults == null) {
                $defaults = DB::table('tofromaliases')
                ->where('account_id', $accountId)
                ->whereRaw("LOWER(origToFrom) = ?", [$lowerToFrom])
                ->first();
            }

            return response()->json($defaults);
        } catch(\Exception $e) {
            error_log("\nProblem getting default values for toFrom: " . $toFrom . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // get clearedBalance, registerBalance, lastBalanced values for a given account
    public function getBalances($account): JsonResponse
    {

        try {
            $registerBalance = DB::table('transactions')
                ->where('account', $account)
                ->sum('amount');

            $clearedBalance = DB::table('transactions')
                ->where('account', $account)
                ->whereNotNull('clear_date')
                ->sum('amount');

            $lastBalanced = DB::table('transactions')
                ->where('account', $account)
                ->max("LastBalanced");

            return response()->json([
                'register_balance' => $registerBalance,
                'cleared_balance' => $clearedBalance,
                'last_balanced' => $lastBalanced
            ]);
        } catch(\Exception $e) {
            error_log("\nProblem getting balances for account: " . $account . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // get total_key transactions
    public function totalKey($totalKey): JsonResponse
    {
        try {
            $totalKeyTransactions = DB::table('transactions')
                ->where('total_key', $totalKey)
                ->select('id', 'amount', 'total_amt')
                ->get();
            return response()->json($totalKeyTransactions);
        } catch(\Exception $e) {
            error_log("\nProblem getting total_key transaction records for total_key: " . $totalKey . ".");
            error_log(json_encode(['exception' => $e, 'trace' => $e->getTraceAsString(),
            ]));

            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    // form to add new transaction
    public function addTransaction() {
        return view('addtransaction');
    }


    // insert new transaction
    public function writeTransaction(Request $request) {

        $transaction = [];
        $fields = [
            'trans_date',
            'clear_date',
            'account',
            'toFrom',
            'amount',
            'amtMike',
            'amtMaura',
            'method',
            'category',
            'tracking',
            'stmtDate',
            'total_amt',
            'total_key',
            'bucket',
            'notes'
        ];

        foreach($fields as $field) {
            if($request->input($field)) $transaction[$field] = $request->input($field);
            else $transaction[$field] = null;
        }
       
        DB::table('transactions')
            ->insert($transaction);

        return redirect()->route('accounts')->with("acctsMsg", "Transaction added.");

    }   // end of function writeTransaction


    // insert monthly transactions to transactions table for current month
    public function writeMonthlyTransactions(Request $request) {
        
        // note transactions done
        $transRecorded = [];

        // get inputs needed
        $chosens = $request->input('chosen');
        $names = $request->input('name');
        $transDates = $request->input('transDate');
        $accounts = $request->input('account');
        $toFroms = $request->input('toFrom');
        $amounts = $request->input('amount');
        $categorys = $request->input('category');
        $buckets = $request->input('bucket');
        $noteses = $request->input('notes');
        $doTrans = $request->input('dotrans');

        // used to redisplay monthlies with updated info
        $monthlies = $request->input('monthlies');
        $monthlies = json_decode($monthlies);

        // process each transaction, if chosen
        foreach($chosens as $idx=>$chosen) {

            if($chosen == 'true') {
                $transaction = [];
                $transaction['trans_date'] = $transDates[$idx];
                $transaction['account'] = $accounts[$idx];
                $transaction['toFrom'] = $toFroms[$idx];
                $transaction['amount'] = $amounts[$idx];
                $transaction['category'] = $categorys[$idx];
                $transaction['notes'] = $noteses[$idx];

                // set amtMike and amtMaura
                if($categorys[$idx] == 'MikeSpending' || $accounts[$idx] == 'Mike') {
                    $transaction['amtMike'] = $amounts[$idx];
                    $transaction['amtMaura'] = 0;
                } else if($categorys[$idx] == 'MauraSpending' || substr( $accounts[$idx], 0, 5) == 'Maura') {
                    $transaction['amtMaura'] = $amounts[$idx];
                    $transaction['amtMike'] = 0;
                } else {
                        $transaction['amtMaura'] = $amounts[$idx] / 2;
                        $transaction['amtMike'] = $amounts[$idx] / 2;
                }

                // set stmtDate
                $year = substr($transDates[$idx], 2, 2);
                $monthNumber = (int)substr($transDates[$idx], 5, 2 );
                $monthAbbrs = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $month = $monthAbbrs[$monthNumber];
                $transaction['stmtDate'] = $year . '-' . $month;

                // for Disc Savings, set bucket
                if($accounts[$idx] == 'DiscSavings') {
                    $transaction['bucket'] = $buckets[$idx];
                }

                // insert the transaction
                $result = DB::table('transactions')
                    ->insert($transaction);
         
                // update monthlies so it's reflected on the page
                $monthlies[$idx]->trans_date = $transaction['trans_date'];
                $monthlies[$idx]->status = 'Pending';

                // add this to transactions recorded
                //  (with reminder to DO the transaction, if needed)

                $transRecorded[] = [
                    'name' => $names[$idx],
                    'account' => $accounts[$idx],
                    'to_from' => $toFroms[$idx],
                    'amount' => $amounts[$idx],
                    'category' => $categorys[$idx],
                    'dotrans' => $doTrans[$idx]
                ];
            }
        }

        // reload the page with the new monthlies
        return view('monthlies', ['monthlies' => $monthlies, 'transRecorded' => $transRecorded]);

    }   // end of function writeMonthlyTransactions


    // save changes to default monthly transactions info
    // public function saveMonthly($id, $name, $account, $dateOfMonth, $toFrom, $amount, $category, $bucket, $notes, $comments) {
    public function saveMonthly(Request $request, $id) {

        $monthlyTransaction = Monthly::find($id);
        \Log::info('Request Details:', [
            // 'headers' => $request->header(),
            // 'method' => $request->method(),
            // 'content_type' => $request->header('Content-Type'),
            // 'raw_content' => $request->getContent(),
            'all_data' => $request->all()
        ]);
        $result = $monthlyTransaction->update(
            $request->only(
                ['name', 
                'dateOfMonth',
                'trans_date',
                'account',
                'toFrom',
                'amount',
                'category',
                'bucket',
                'notes',
                'comments']
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully!'
        ]);
    }   // end of function saveMonthly


    // write transactions to database for GB Limo pay & spending
    // 6 transactions total:
    // - one for gross paycheck
    // - one for taxes withheld from paycheck
    // - two each for Mike/Maura Spending (4 total)
    // --- out of checking; into respective spending accts
    public function writeGBLimo(Request $request) {

        // transaction for gross paycheck deposit to checking
        function writeGBgrossPay($request) {
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbpaycheckdate');
            $transaction['clear_date'] = $request->input('gbpaycheckdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = "Great Bay Limo";
            // gross includes net + tax w/h + ss w/h + medicare w/h
            $grossPay = $request->input('gbnetpay') + $request->input('gbtaxwh') + $request->input('gbsswh') + $request->input('gbmcwh');
            $transaction['amount'] = $grossPay;
            $transaction['amtMike'] = $grossPay / 2;
            $transaction['amtMaura'] = $grossPay / 2;
            $transaction['method'] = 'ACH';
            $transaction['category'] = 'IncomeMisc';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['total_amt'] = $request->input('gbnetpay');
            $transaction['total_key'] = "ggg";
            $transaction['notes'] = $request->input('gbpayperiodnote');
           
            $payId = DB::table("transactions")
                ->insertGetId($transaction);

            // set the total_key to the id of the record just inserted.
            DB::table("transactions")
                ->where("total_key", "ggg")
                ->update(["total_key" => $payId]);

            return $payId;
        }

        // transaction for tax withheld from deposit to checking and
        // transaction for ss and medicare withheld
        function writeGBtaxWH($request, $payId) {

            // tax withheld (if any)
            if($request->input('gbtaxwh') != 0) {
                $transaction = [];
                $transaction['trans_date'] = $request->input('gbpaycheckdate');
                $transaction['clear_date'] = $request->input('gbpaycheckdate');
                $transaction['account'] = "Checking";
                $transaction['toFrom'] = "Great Bay Limo";
                $transaction['amount'] = -$request->input('gbtaxwh');
                $transaction['amtMike'] = -$request->input('gbtaxwh') / 2;
                $transaction['amtMaura'] = -$request->input('gbtaxwh') / 2;
                $transaction['method'] = 'ACH';
                $transaction['category'] = 'IncomeTaxes';
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['total_amt'] = $request->input('gbnetpay');
                $transaction['total_key'] = $payId;
                $transaction['notes'] = $request->input('gbpayperiodnote');
            
                $result = DB::table("transactions")
                    ->insert($transaction);
                if(!$result) error_log("ERROR inserting TAX WITHHELD to transactions table: " . json_encode($result));
            }

            // ss & medicare withheld (if any)
            $otherWithheld = $request->input('gbsswh') + $request->input('gbmcwh');
            if($otherWithheld <> 0) {
                $transaction = [];
                $transaction['trans_date'] = $request->input('gbpaycheckdate');
                $transaction['clear_date'] = $request->input('gbpaycheckdate');
                $transaction['account'] = "Checking";
                $transaction['toFrom'] = "Great Bay Limo";
                $transaction['amount'] = -$otherWithheld;
                $transaction['amtMike'] = -$otherWithheld / 2;
                $transaction['amtMaura'] = -$otherWithheld / 2;
                $transaction['method'] = 'ACH';
                $transaction['category'] = 'IncomeOtherWH';
                $transaction['stmtDate'] = $request->input('gbstmtdate');
                $transaction['total_amt'] = $request->input('gbnetpay');
                $transaction['total_key'] = $payId;
                $transaction['notes'] = $request->input('gbpayperiodnote');
            
                $result = DB::table("transactions")
                    ->insert($transaction);
                if(!$result) error_log("ERROR inserting TAX WITHHELD to transactions table: " . json_encode($result));
            }

            return;
        }

        // write spending transactions for Mike or Maura ($MorM) (checking to spending)
        function writeGBspending($request, $MorM) {

            // Checking to spending record
            $transaction = [];
            $transaction['trans_date'] = $request->input('gbspendingdate');
            $transaction['clear_date'] = $request->input('gbspendingdate');
            $transaction['account'] = "Checking";
            $transaction['toFrom'] = $MorM;
            $grossSpending = $request->input('gbnetpay') + $request->input('gbtaxwh');
            $transaction['amount'] = -$request->input('gbspending');
            $transaction['amtMike'] = -$request->input('gbspending') / 2;
            $transaction['amtMaura'] = -$request->input('gbspending') / 2;
            $transaction['method'] = 'Internet';
            $transaction['category'] = 'ExtraSpending';
            $transaction['stmtDate'] = $request->input('gbstmtdate');
            $transaction['notes'] = $request->input('gbspendingnote');
           
            DB::table("transactions")
                ->insert($transaction);

            // Spending from Checking record
            // Only make needed changes to transaction record
            $transaction['account'] = $MorM;
            $transaction['toFrom'] = "Checking";
            $transaction['amount'] = $request->input('gbspending');
            if($MorM == "Mike") {
                $transaction['amtMike'] = $request->input('gbspending');
                $transaction['amtMaura'] = 0;
            } else {
                $transaction['amtMaura'] = $request->input('gbspending');
                $transaction['amtMike'] = 0;
            }
            unset($transaction['category']);

            DB::table("transactions")
                ->insert($transaction);

            return;
        }

        // transaction for gross paycheck deposit to checking
        // $payId is the id for the paycheck record,
        //      used as total_amt to tie this and the next record as a split transaction
        $payId = writeGBgrossPay($request);

        // transaction for ss, medicare, & tax withheld from deposit to checking
        writeGBtaxWH($request, $payId);

        // write spending transactions for Mike (checking to spending)
        writeGBspending($request, "Mike");

        // write spending transactions for Maura (checking to spending)
        writeGBspending($request, "MauraSCU");

        // go back to accounts page, with reminder wrt transfer
        $reminder = "REMEMBER to transfer " . $request->input('gbspending') . " each to Mike's and Maura's spending accounts, and write transactions in checkbook.";
        return redirect()->route('accounts')->with('acctsMsg', $reminder);
    }   // end of writeGBLimo


    // write odometer reading to carcostdetails table
    function writeOdom($tripData) {
        $errMsg = null;

        $readingDate = $tripData['tripOdomDate'];
        $reading = $tripData['tripOdom'];
        $car = $tripData['tripCar'];

        $key = 'Mileage' . substr($readingDate, 2, 2) . substr($readingDate, 5, 2) . substr($readingDate, 8, 2);

        $insertRcd = [];
        $insertRcd['car'] = $car;
        $insertRcd['key'] = $key;
        $insertRcd['value'] = $reading;

        $result = DB::table('carcostdetails')
            ->insert($insertRcd);

        // check for error
        if(!$result) $errMsg = "Error writing odometer reading to carcostdetails table.  " . json_encode($result);

        return $errMsg;
    }   // end of writeOdom


    function calcSharePurchase($tripData, $purchase, $beginMiles, $expMiles) {

        // calc purchase cost per mile
        $costPerMile = round($purchase/($expMiles - $beginMiles), 3);

        // cost/mile * number of miles for this trip is the share of the purchase price for this trip
        $sharePurchase = round($costPerMile * $tripData["tripmiles"], 2);

        return $sharePurchase;

    }   // end of function calcSharePurchase


    // calculate share of maintenance costs for this trip
    function calcShareMaint($tripData, $beginMiles, $recentMileage) {
        // error_log("\n\n--------------------");

        // sum total cost of maintenance 2022 or later.
        // result is negative, so sign needs to be reversed.
        $recentMaint = DB::table('transactions')
            ->where('tracking', $tripData['tripCar'])
            ->where('notes', 'like', 'maint%')
            ->where('trans_date', '<=', $tripData['tripBegin'])
            ->get()->toArray();
        
        // sum recent maint costs (those in transactions) for this car to $recentMaintTotAmt
        // and pull last $numbMaintTransToCheck maintenance transactions for this car in $checkRecentMaint
        $numbMaintTransToCheck = 5;
        $numRecentMaint = count($recentMaint);
        $checkRecentMaint = [];
        $recentMaintTotAmt = 0;
        foreach($recentMaint as $maintKey=>$maintRcd) {
            $recentMaintTotAmt -= $maintRcd->amount;
            if($maintKey >= $numRecentMaint-$numbMaintTransToCheck) {
                $checkRecentMaint[] = [
                    'Date' => $maintRcd->trans_date,
                    'Business' => $maintRcd->toFrom,
                    'Account' => $maintRcd->account,
                    'Tracking' => $maintRcd->tracking,
                    'Category' => $maintRcd->category,
                    'Notes' => $maintRcd->notes
                ];
            }
        }
        // error_log("*** recentMaintTotAmt: " . $recentMaintTotAmt);

        // sum maintenance before 2022 (not in transactions table) to $oldMaint
        $oldMaint = DB::table('carcostdetails')
            ->where('car', $tripData['tripCar'])
            ->where('key', 'OldMaint')
            ->pluck('value');
        // error_log("*** oldMaint: " . $oldMaint);
        
        // if no old maintenance found, set to 0
        if(count($oldMaint) > 0) {
            $oldMaint = $oldMaint[0];
        } else {
            $oldMaint = 0;
        }
        // error_log("*** oldMaint: " . $oldMaint);
        
        // total maintenance is old + new
        $totMaint = $recentMaintTotAmt + $oldMaint;
        // error_log("*** totMaint: " . $totMaint);

        // calc maint cost per mile
        // error_log("recentMileage: " . $recentMileage);
        // error_log("beginMiles: " . $beginMiles);
        // error_log("diff: " . ($recentMileage - $beginMiles));
        $costPerMile = $totMaint/($recentMileage - $beginMiles);
        // error_log("cost per mile: " . $costPerMile);
        
        // cost/mile * number of miles for this trip is the share of the maintenance cost for this trip
        $shareMaint = round($costPerMile * $tripData['tripmiles'], 2);
        error_log("shareMaint: " . $shareMaint);

        return [$shareMaint, $checkRecentMaint];
        
    }   // end of function calcShareMaint


    // calculate share of insurance premium for this trip
    // NOTE:  This ASSUMES
    //
    //      IMPORTANT
    //      ASSUMPTION:  all ins data for the same term will be together in the db table!!!
    //
    //
    function calcShareIns($tripData, $beginMiles, $expMiles) {

        // get all insurance inforamtion
        // find what is relevant in the code below
        $insTableData = DB::table('carcostdetails')
            ->where('car', $tripData['tripCar'])
            ->where('key', 'like', 'InsPay%')
            ->get()->toArray();

        // init vars to organize data
        $begins = [];
        $ends = [];
        $miles = [];
        $premiums = [];
        $prefixes = [];
        $errMsg = null;

        foreach($insTableData as $insDatum) {
            if(str_contains($insDatum->key, 'Begin')) {
                $begins[] = $insDatum->value;
            } else if(str_contains($insDatum->key, 'End')) {
                $ends[] = $insDatum->value;
            } else if(str_contains($insDatum->key, 'Miles')) {
                $miles[] = $insDatum->value;
            } else {
                $premiums[] = $insDatum->value;
                $prefixes[] = $insDatum->key;
            }
        }

        // error_log("begins: " . json_encode($begins));
        // error_log("ends: " . json_encode($ends));
        // error_log("premiums: " . json_encode($premiums));
        // error_log("miles: " . json_encode($miles));
        // error_log("prefixes: " . json_encode($prefixes));

        // find which prefix for the key to use
        $found = false;
        foreach($prefixes as $prefixIdx=>$prefix) {
            if(
                $tripData['tripBegin'] >= $begins[$prefixIdx] 
                && $tripData['tripBegin']<= $ends[$prefixIdx]
            ) {
                $found = true;
                break;
            }
        }
        // error_log("\n----");
        // error_log("found: " . ($found ? "true" : "false));
        // error_log("prefix: " . $prefix);
        // error_log("begin: " . $begins[$prefixIdx]);
        // error_log("end: " . $ends[$prefixIdx]);
        // error_log("miles: " . $miles[$prefixIdx]);
        // error_log("premium: " . $premiums[$prefixIdx]);

        if(!$found) {
            $errMsg = "No Insurance Premium for this time frame was found in the carcostdetails table.";
            $costPerMile = 0;
        } else {
            // $prefixIdx now has index into the relevant data for all the insurance info
            // calc maint cost per mile
            $costPerMile = $premiums[$prefixIdx]/$miles[$prefixIdx];
        }

        // cost/mile * number of miles for this trip is the share of the maintenance cost for this trip
        $shareIns = round($costPerMile * $tripData['tripmiles'], 2);

        return [$shareIns, $errMsg];
        
    }   // end of function calcShareIns


    // calculate fuel cost for this trip
    function calcFuel($tripData) {
    // NOTE:  This ASSUMES
    //
    //      IMPORTANT
    //      ASSUMPTION:  
    //          All gas/charging done is in the database
    //              with notes like 'trips - <trip name>'
    //              and gallons or kWhs purchased
    //

    // Need:
    //      miles traveled  
    //          $tripData['tripMiles']
    //      gas/kWh purchased en route
    //          in transactions table where notes = "% - trips - <tripName> ..."
    //      MPG or mile/kwh
    //          in carcostdetails table
    //              key = "Fuel" tells if gas or electric is used
    //              for electric: solar kWh/mile is where key = "MPK"
    //              for gas: mpg is where key = "MPG"
    //      cost of fuel not bought on trip
    //          electric: in carcostdetails table where key = "SolarKwh"
    //          gas:    in transactions table, last where 


        // get data keys needed in carcostdetails
        function getCarCostData($tripData) {
            $errMsg = null;     // assume no msgs to start

            // keys in carcostdetails table for data needed
            $keys = [
                'Fuel',
                'MPK',
                'MPG',
                'SolarKwh'
            ];

            // get data
            $carCostInfo = DB::table('carcostdetails')
                ->where('car', $tripData['tripCar'])
                ->whereIn('key', $keys)
                ->get()->toArray();
            // error_log("------ carCostInfo:");
            // error_log(json_encode($carCostInfo));

            // put in more usable format
            $fuel = null;
            $MPG = null;
            $MPK = null;
            $SolarKwh = null;

            foreach($carCostInfo as $info) {
                switch ($info->key) {
                    case 'Fuel':
                        $fuel = $info->value;
                        break;
                    case 'MPG':
                        $MPG = $info->value;
                        break;
                    case 'MPK':
                        $MPK = $info->value; 
                        break;
                    case 'SolarKwh':
                        $SolarKwh = $info->value;
                        break;
                    default:
                        $errMsg = '\n\n  NOTE:  *** Undefined carCostInfo key found: ' . $info->key . "\n\n";
                        break;
                }
            }

            return [$fuel, $MPK, $MPG, $SolarKwh, $errMsg];

        }  // end of function getCarCostData


        // get fuel bought info (volume and cost)
        function getFuelBoughtInfo($tripData, $fuel) {
            $msg = null; // assume no msgs to start
           
            // gets gas bought, if any
            $gasBought = DB::table('transactions')
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', '%gas - trips - ' . $tripData['tripName'] . '%');

            // gets kwh bought, if any
            $kwhBought = DB::table('transactions')
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', '%charg% - trips - ' . $tripData['tripName'] . '%');

            // all fuel bought (should be just all gas or kwh)
            $fuelBoughtEnRoute = $gasBought ->unionAll($kwhBought)->get()->toArray();

            // error_log("------ fuelBought:");
            // error_log(json_encode($fuelBoughtEnRoute));
    
            // put in usable format
            $fuelVolumeEnRoute = 0;
            $fuelCostEnRoute = 0;
            $needUnitCost = false;
            foreach($fuelBoughtEnRoute as $fuelEvent) {
                [$cost, $vol, $unitCost, $msg] = findFuelCostAndAmt($fuelEvent, $fuel, $needUnitCost); // unitCost will be null
                $msg .= $msg;
                $fuelVolumeEnRoute += $vol;
                $fuelCostEnRoute -= $cost;      // costs are negative in transactions table, positive here
            }

            return [$fuelVolumeEnRoute, $fuelCostEnRoute, $msg];
        }   // end of function getFuelBoughtInfo


        // get how much purchase (amt) and what cost from record where fuel was purchase en route
        function findFuelCostAndAmt($fuelEvent, $fuel, $needUnitCost) {
            $msg = '';  // assume no msg's until something found.

            if(!$needUnitCost) {
                // get cost of fuel from record
                $cost = $fuelEvent->amount;

                // get amt from "notes" column
                if($fuel == 'electric') {
                    // needs to have " ##.## kwh"
                    $volPattern = '/(\d+(?:\.\d+)?)\s*kwh/i';

                } else if ($fuel == 'gas') {
                    // needs to have " ##.## gal"
                    $volPattern = '/(\d+(?:\.\d+)?)\s*per gal/i';
                }

                preg_match($volPattern, $fuelEvent->notes, $matches);
                // error_log("matches: " . json_encode($matches));

                // Get the matched number
                $amt = $matches[1]; // Will contain string of volume purchased
                if($amt == '' || $amt == null) {
                    $msg = "No amount found.";
                } else {
                    // Convert to float if needed
                    $amt = floatval($amt);
                }
            } else {
                $cost = null; // not requested
                $amt = null;  // not requested
            }

            if($needUnitCost) {
                // get unit cost
                $unitPattern = '/@ *([\d.]+)/';
                preg_match($unitPattern, $fuelEvent->notes, $matches);

                if ($matches) {
                    $unitCost = $matches[1];
                } else {
                    $unitCost = null;
                    $msg .= "  No unit cost found.";
                }
            } else {
                $unitCost = null;   // not requested
            }

            return [$cost, $amt, $unitCost, $msg];
        }   // end of function findFuelCostAndAmt
    
    
        $errMsg = null;

        // get data keys needed in carcostdetails
        [$fuel, $MPK, $MPG, $SolarKwh, $errMsg] = getCarCostData($tripData);
        // error_log("\n\nfuel: " . $fuel . "\nMPG: " . $MPG . "\nMPK: " . $MPK . "\nSolarKwh: " . $SolarKwh . "\nerrMsg: " . $errMsg);

        // get fuel bought info (volume and cost)
        [$fuelVolumeEnRoute, $fuelCostEnRoute, $msg] = getFuelBoughtInfo($tripData, $fuel);
        if($msg != null) $errMsg .= "  " . $msg;
        // error_log("\n\nfuel vol en route: " . $fuelVolumeEnRoute);
        // error_log("\n\nfuel cost en route: " . $fuelCostEnRoute);
        // error_log("\n\nerrMsg: " . $errMsg);



        // get last time gas was bought (not on a trip) BEFORE this trip
        // if($tripData['tripCar'] == 'CRZ') {
        if($fuel == 'gas') {
            $lastGases = DB::table('transactions')
                ->where('trans_date', '<=', $tripData['tripBegin'])
                ->where('tracking', $tripData['tripCar'])
                ->where('notes', 'like', 'gas%')
                ->where('notes', 'not like', '%- trips %')
                ->where('notes', 'not like', '%- trip %')
                ->orderBy('trans_date', 'desc')
                ->get()->toArray();
            // need at least one lastGases record
            if(count($lastGases) == 0) {
                $errMsg .= "Need to be able to see how much was paid for gas recently.\n\n";
                $recentGasCost = 0;
                $recentGasVolume = 0;
                $recentUnitPrice = 0;
            } else {
                $lastGas = $lastGases[0];
                // error_log("------ lastGas:");
                // error_log(json_encode($lastGas));

                $needUnitCost = true;
                // recentGasCost and recentGasVolume not needed, should be null
                [$recentGasCost, $recentGasVolume, $recentUnitPrice, $msg] = findFuelCostAndAmt($lastGas, $fuel, $needUnitCost); 
                $errMsg .= $msg;

                // error_log("-- recentUnitPrice: " . $recentUnitPrice);
            }
        }

        // Have data needed to calc fuel cost.
        if($fuel == 'electric') {
            // Charging en route + Home charging
            // Need est kWh used: Total miles / MPK
            $totalKwhUsed = $tripData['tripmiles'] / $MPK;
            $gallonsKwHused = $totalKwhUsed;
            error_log("total kwh used: " . $totalKwhUsed);

            // fuel not purchased en route
            $fuelVolumeNotBoughtEnRoute = $totalKwhUsed - $fuelVolumeEnRoute;
            // error_log("totalkwhused: " . $totalKwhUsed);
            // error_log("fuelVolumeEnRoute: " . $fuelVolumeEnRoute);
            // error_log("fuelVolumeNotBoughtEnRoute: " . $fuelVolumeNotBoughtEnRoute);

            // cost of fuel not bought en route
            $fuelCostNotBoughtEnRoute = $fuelVolumeNotBoughtEnRoute * $SolarKwh/100;
            
            error_log("fuel cost bought en route (kwh): " . $fuelCostEnRoute);
            error_log("fuel cost not bought en route (kwh): " . $fuelCostNotBoughtEnRoute);

            // total fuel cost = bought en route + not bought en route
            $fuelCost = $fuelCostEnRoute + $fuelCostNotBoughtEnRoute;

        } else if($fuel == 'gas') {
            // Gas bought en route + gas already in tank that was used
            // Need est gallons used: Total miles / MPG
            $totalGalUsed = $tripData['tripmiles'] / $MPG; 
            $gallonsKwHused = $totalGalUsed;   
            error_log("total gallons used: " . $totalGalUsed);

            // fuel not purchased en route
            $fuelVolumeNotBoughtEnRoute = $totalGalUsed - $fuelVolumeEnRoute;
            // error_log("totalGalused: " . $totalGalUsed);
            // error_log("fuelVolumeEnRoute: " . $fuelVolumeEnRoute);
            // error_log("fuelVolumeNotBoughtEnRoute: " . $fuelVolumeNotBoughtEnRoute);

            // cost of fuel not bought en route
            $fuelCostNotBoughtEnRoute = $fuelVolumeNotBoughtEnRoute * $recentUnitPrice;
            error_log("recent unit price/gal: " . $recentUnitPrice);
            
            error_log("fuel cost bought en route (gas): " . $fuelCostEnRoute);
            error_log("fuel cost not bought en route (gas): " . $fuelCostNotBoughtEnRoute);

            // total fuel cost = bought en route + not bought en route
            $fuelCost = $fuelCostEnRoute + $fuelCostNotBoughtEnRoute;

        } else $errMsg .= "  Invalid fuel found on carCostDetails table.";

        // $errMsg = trim($errMsg);
        // error_log("errMsg (end of calcFuel): " . $errMsg);
        return [round($fuelCost,2), round($gallonsKwHused,2), $errMsg];
        
    }   // end of function calcFuel


    // calc values & write transactions for cost of car for a trip
    // 2 transactions total (in & out of household checking) -- from MxxxSpending to IncomeMisc categories
    // - one from Spending (MikeSpending and/or MauraSpending) category (to charge who used the car)
    // - second to Checking with category IncomeMisc (because money never really left the ckg account)
    public function recordTrip(Request $request) {

        // Do we have all the info?  Assume complete until find something missing
        $completeTripInfo = true;
        // error_log("completeTripInfo (init): " . $completeTripInfo);

        // in case there are messages
        $errMsg = null;

        // fields to retrieve (ids) from page (form)
        $fields=[
            "tripName",
            "tripBegin",
            "tripEnd",
            "tripWho",
            "tripCar",
            "tripOdom",
            "tripOdomDate",
            "tripmiles",
            "tripTolls"
        ];

        foreach($fields as $field) {
            if($request->input($field)) $tripData[$field] = $request->input($field);
            else $tripData[$field] = null;
        }
        // error_log("tripData: " . json_encode($tripData));

        // write odometer reading, if it was entered
        if($tripData['tripOdom'] != '' && $tripData['tripOdomDate'] != '') {
            $msg = $this->writeOdom($tripData);
            // Go no further if error recording odometer reading
            if($msg != null) {

                // get cars, drivers, and most recent mileage from carcostdetails table
                $carInfo = $this->getCarInfo();

                // get an array of all the trip names
                $tripNames = $this->getTripNames();

                $errMsg = '**** No TRIP recorded.  Need correct odometer reading! ****';
                return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames, 'errMsg' => ($msg . "\n" . $errMsg)]);
            }
        }

        // Go no further if tripEnd is before tripBegin
        if($tripData['tripEnd'] < $tripData['tripBegin']) {

            // get cars, drivers, and most recent mileage from carcostdetails table
            $carInfo = $this->getCarInfo();

            // get an array of all the trip names
            $tripNames = $this->getTripNames();
        
            $errMsg = '**** No TRIP recorded.  Trip must begin before it ends! ****';
            return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames,'errMsg' => $errMsg]);
        }

        // get data needed from carcostdetails table
        // get purchase price of car, begin mileage & est total (end) mileage
        $dataNeeded = DB::table('carcostdetails')
            ->select("key", "value")
            ->where("car", $tripData["tripCar"])
            ->whereIn("key", ["Purchase", "BeginMiles", "ExpMiles"])
            ->get()->toArray();

        // get most recent mileage
        $recentMileage = DB::table('carcostdetails')
            ->select("key", "value")
            ->where("car", $tripData["tripCar"])
            ->where("key", "like", "Mileage%")
            ->max("value");
        // error_log("recentMileage: " . json_encode($recentMileage));

        // pull data out of results
        foreach($dataNeeded as $dataRcd) {
            switch($dataRcd->key) {
                case "Purchase":
                    $purchase = $dataRcd->value;
                    break;
                case "BeginMiles";
                    $beginMiles = $dataRcd->value;
                    break;
                case "ExpMiles";
                    $expMiles = $dataRcd->value;
                    break;
            }
        }

        // calc different costs
        
        // share of purchase price
        $tripData["sharePurchase"] = $this->calcSharePurchase($tripData, $purchase, $beginMiles, $expMiles);
        
        // share of maintenance costs
        [$tripData["shareMaint"], $checkRecentMaint] = $this->calcShareMaint($tripData, $beginMiles, $recentMileage);

        // add checkRecentMaint to errMsg
        $msgIntro = "If these are NOT the most recent maint transactions, add those and re-calc the trip:";
        $errMsg .= "\n" . $msgIntro . "\n";
        foreach($checkRecentMaint as $maintTrans) {
            foreach($maintTrans as $key=>$maintItem) {
                $errMsg .= " - " . str_pad($key . ":", 10) . "\t" . $maintItem . "\n";
            }
            $errMsg .= "\n";
        }

        // share of insurance payments
        [$tripData["shareIns"], $msg] = $this->calcShareIns($tripData, $beginMiles, $expMiles);
        if($msg != null) $completeTripInfo = false;
        // error_log("completeTripInfo (sharIns): " . $completeTripInfo);

        $errMsg .= $msg;
        // error_log("sharePurchase: " . $tripData['sharePurchase']);
        // error_log("shareMaint: " . $tripData['shareMaint']);
        // error_log("shareIns: " . $tripData['shareIns']);
        // error_log(" -------------- ");
        // error_log("errMsg:" . $errMsg);
        
        // fuel (gas or charging) for this trip
        //      handle gas/charging purchased during trip
        [$tripData["fuelCost"], $tripData['gallonsKwHused'], $msg] = $this->calcFuel($tripData);
        if($msg != null) $completeTripInfo = false;
        // error_log("completeTripInfo (calcFuel): " . $completeTripInfo);

        $errMsg = $msg . $errMsg;
        // error_log("errMsg (after calcFuel): " . $errMsg);

        // error_log("Fuel cost: " . $tripData['fuelCost']);
        error_log("gallonsKwHused: " . $tripData['gallonsKwHused']);

        // error_log("errMsg:" . $errMsg);
        $newTripRcd = [];
        $newTripRcd['trip'] = $tripData['tripName'];
        $newTripRcd['who'] = $tripData['tripWho'];
        $newTripRcd['car'] = $tripData['tripCar'];
        $newTripRcd['begin'] = $tripData['tripBegin'];
        $newTripRcd['end'] = $tripData['tripEnd'];
        $newTripRcd['tolls'] = $tripData['tripTolls'] ?? 0;
        $newTripRcd['mileage'] = $tripData['tripmiles'];
        $newTripRcd['sharePurchase'] = $tripData['sharePurchase'];
        $newTripRcd['shareIns'] = $tripData['shareIns'];
        $newTripRcd['shareMaint'] = $tripData['shareMaint'];
        $newTripRcd['gallonsKwHused'] = $tripData['gallonsKwHused'];
        $newTripRcd['gasChargingDollars'] = $tripData['fuelCost'];
        $newTripRcd['other'] = 0;

        // write record to the trips table.
        $result = DB::table("trips")->insert($newTripRcd);
        if($result) error_log("TRIPS record written");
        else error_log("TRIPS record NOT written: " . json_encode($result));

        // trip errMsg
        // $errMsg = trim($errMsg);
        // error_log("completeTripInfo (end): " . $completeTripInfo);

        if($completeTripInfo) {
            $errMsg = "\n**** Trip recorded. Total cost was " 
            . ($tripData['tripTolls'] + $tripData['sharePurchase'] + $tripData['shareIns'] + $tripData['shareMaint'] + $tripData['fuelCost'] + ($tripData['other'] ?? 0))
            . ". ****\n" 
            . "**** REMEMBER!!! Transfer the cost of the trip from Mike/Maura-Spending!\n\n"
            . $errMsg;
        } else {
            $errMsg = "PARTIAL trip recorded.\n\n" 
            . "May need to fix problems and re-calc trip."
            . "\nIf not, REMEMBER to transfer the cost of the trip from Mike/Maura-Spending!\n\n"
            . $errMsg;
        }

        // load trips page with msg containing what was done, errors, warning.
        // get cars, drivers, and most recent mileage from carcostdetails table
        $carInfo = $this->getCarInfo();

        // get an array of all the trip names
        $tripNames = $this->getTripNames();

        return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames,'errMsg' => $errMsg]);


    }   // end of recordTrip
    

    // Show assets and current values
    public function assets($endDate = null) {

        // Input param: 
        //  accounts - all transaction records for accounts
        //  array of accountNames to process
        //
        // Returns:
        //  only those records with the most recent lastBalanced value
        function getLatest($accounts, $accountNames) {

            // init arrays to keep track of most recent lastBalanced info
            // newAccounts - records with the most recent data for each account
            // lastBalancedDates - most recent lastBalanced date found for each account
            $newAccounts = [];
            $lastBalancedDates = [];

            // init these arrays
            foreach($accountNames as $accountName) {
                $newAccounts[$accountName] = null;
                $lastBalancedDates[$accountName] = null;
            }

            foreach($accounts as $account) {

                // get the account name
                $accountName = $account->account;

                // if this is the first record encountered for this account, keep the info
                if($newAccounts[$accountName] == null) {
                    $newAccounts[$accountName] = $account;
                    $lastBalancedDates[$accountName] = $account->max_last_balanced;
                // if this record's lastBalanced is later than the one saved, replace the old data with this
                } else if(
                    $lastBalancedDates[$accountName] == null ||
                    $lastBalancedDates[$accountName] < $account->max_last_balanced
                ) {
                    $lastBalancedDates[$accountName] = $account->max_last_balanced;
                    $newAccounts[$accountName] = $account;
                }
                    
            }

            // fill in default values for null records
            $accounts = [];
            foreach($newAccounts as $key=>$account) {
                if($account != null) {
                    $accounts[] = $account;
                } else {
                    $accounts[$key] = (object) [
                        'account' => $account,
                        'amount' => 0,          // amount
                        'lastBalanced' => date("Y-m-d"),  // last balanced
                        'type' => ''
                    ];
                }
            }

            // don't need high level keys
            $newAccounts = array_values($newAccounts);
            
            // return just the records with the most recent lastBalanced dates (one / account)
            return $accounts;

        }

        // get total assets
        function getTotalAssets($accounts) {
            $totalLine = [];
            $totalLine['account'] = 'Total';
            $totalLine['amount'] = array_sum(array_column($accounts, 'amount'));
            $totalLine['max_last_balanced'] = max(array_column($accounts, 'max_last_balanced'));
            $totalLine['type'] = "Total";

            return (object) $totalLine;
        }

        // set endDate if not passed in
        if($endDate == null) {
            $endDate = date('Y-m-d');
        }

        // get all transactional account names
        $transAccountNames = DB::table('accounts')
            ->where("type", "trans")
            ->distinct()->get("accountName")->toArray();
        $transAccountNames = array_column($transAccountNames, 'accountName');

        // get all investment account names
        $invAccountNames = DB::table('accounts')
            ->where("type", "inv")
            ->distinct()->get("accountName")->toArray();
        $invAccountNames = array_column($invAccountNames, 'accountName');

        // get record for each transactional account
        $transAccounts = DB::table('transactions')
            ->select(
                'account',
                // DB::raw('SUM(CASE WHEN clear_date <= ' . $endDate . ' THEN amount ELSE 0 END) AS amount'),
                DB::raw('SUM(amount) AS amount'),
                DB::raw('MAX(lastBalanced) AS max_last_balanced'),
                DB::raw("'Trans' AS type")
            )
            ->whereIn(
                'account',
                $transAccountNames
            )
            ->whereDate('clear_date', '<=', $endDate)       // new
            ->groupBy('account')
            ->get()
            ->toArray();
        
        // order trans accounts alphabetically
        usort($transAccounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        // get record for each investment account
        $invAccounts = DB::table('transactions')
            ->select(
                'account',
                'amount',
                'lastBalanced AS max_last_balanced',
                DB::raw("'Inv' AS type"),
            )
            ->where('category', "VALUE")
            ->whereIn(
                'account',
                $invAccountNames
            )
            ->where('clear_date', '<=', $endDate)
            ->get()
            ->toArray();

        // get transaction with latest clear_date for each account
        $invAccounts = getLatest($invAccounts, $invAccountNames);

        // order inv accounts alphabetically
        usort($invAccounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        // merge the accounts arrays
        $accounts = array_merge($transAccounts, $invAccounts);

        // add Total line to $accounts
        $accounts[] = getTotalAssets($accounts);

        // format $ amounts  for each account
        foreach($accounts as $acctIdx=>$account) {
            $accounts[$acctIdx]->amount = number_format($account->amount, 2);
        }

        return view('assets', ['accounts' => $accounts]);
    }   // end of function assets


    // Prompt for input needed, and process GB Limo paycheck
    public function gblimo() {

        // get defaults for page

        // paycheck date - one week since last one
        $gboldpaycheckdate = DB::table('transactions')
            ->where('toFrom', 'Great Bay Limo')
            ->max('trans_date');

            // add a week to it
        $newpaycheckdate = new \DateTime($gboldpaycheckdate);
        $newpaycheckdate->modify("+1 week");
        $newpaycheckdate = $newpaycheckdate->format("Y-m-d");
        
        // spending transfer date (defaults to today)
        $timezone = new \DateTimeZone('America/New_York');
        $today = new \DateTime('now', $timezone);
        $gbspendingdate = $today->format("Y-m-d");

        // statement date can default to the spending date

        return view('gblimo', ['gbpaycheckdate' => $newpaycheckdate, 'gbspendingdate' => $gbspendingdate, 'gbstmtdate' => $gbspendingdate]);

    }   // end of function gblimo


    // Set up & do monthly transactions
    public function monthly() {

        // get monthly transactions set up
        $monthlies = DB::table('monthlies')
            ->get()->toArray();

        // get last cleared_date (or last trans_date if cleared is null) for each monthly transaction
        // if cleared_date is null, transaction is Pending, else it is Completed.
        foreach($monthlies as $monIdx=>$month) {
            // basic query to get most recent monthly transactions
            $dates = DB::table('transactions')
                ->where('toFrom', $month->toFrom)
                ->where('account', $month->account)
                ->where('notes', 'LIKE', $month->notes . '%')
                // ->where('amount', $month->amount)        -- only do this (below) when amount is consistent
                // ->where('category', $month->category)    -- only do this (below) if category is consistent
                ->select(
                    DB::raw("CASE 
                        WHEN clear_date IS NOT NULL THEN clear_date 
                        ELSE trans_date 
                    END as date"),
                    DB::raw("CASE 
                        WHEN clear_date IS NOT NULL THEN 'Completed'
                        ELSE 'Pending'
                    END as status")
                )
                ->orderByRaw("CASE 
                    WHEN clear_date IS NOT NULL THEN clear_date
                    ELSE trans_date
                END DESC") // puts the most recent first
                ->get();

            // if amount is set, filter by that
            if(!is_null($month->amount)) {
                $dates->where('amount', $month->amount);
            }

            // match category, if needed
            if(!$month->anyCategory) {
                $dates->where('category', $month->category);
            }

            // get the first transaction (they've been sorted by most recent first)
            $dates = $dates->first();

            if($dates == NULL) {
                // this shouldn't happen - means there's probably an error in the database
                $monthlies[$monIdx]->trans_date = 'not found';
                $monthlies[$monIdx]->status = 'unknown';
            } else {
                // add dates and statuses to monthlies array
                $monthlies[$monIdx]->trans_date = $dates->date;
                $monthlies[$monIdx]->status = $dates->status;
            }

        }

        // Sort by status and regular date to display in an orderly fashion
        usort($monthlies, function($a, $b) {
            if ($a->status === $b->status) {
                return (int) $a->dateOfMonth > (int) $b->dateOfMonth;
            }
            return strcmp($a->status, $b->status);
        });
        
        // no recorded transactions to show here
        return view('monthlies', ['monthlies' => $monthlies, 'transRecorded' => [] ]);
    }


    // Prompt for new investment account balances
    public function investmentsindex() {

        function getThreeMonthsAgo() {
            // Get current date/time
            $currentDateTime = new \DateTime();

            // Calculate three months ago
            $threeMonthsAgo = clone $currentDateTime;
            $threeMonthsAgo->modify('-3 months');

            // Set to New York timezone
            $nyTimezone = new \DateTimeZone('America/New_York');
            $threeMonthsAgo->setTimezone($nyTimezone);

            // Format and display the result
            return $threeMonthsAgo->format('Y-m-d');
        }


        // get investment accounts and relavent information
        $threeMonthsAgo = getThreeMonthsAgo();

        // retrieve the recent investment acct info from db
        //  -- most recent first
        $dbinvestments = DB::table('transactions')
            ->where('toFrom', 'Value')
            ->where('lastBalanced', '>=', $threeMonthsAgo)
            ->orderBy('account', 'asc')
            ->orderBy('lastBalanced', 'desc')
            ->get()->toArray();

        // only keep the most recent record for each account
        $investments = [];
        $accountsIncluded = [];
        foreach($dbinvestments as $dbInv) {
            if(!in_array($dbInv->account, $accountsIncluded)) {
                // only keep date part of lastBalanced (not time)
                $dbInv->lastBalanced = substr($dbInv->lastBalanced, 0, 10); // keep yyyy-mm-dd
                // Balance only needs to go to 2 decimal places
                $dbInv->amount = substr($dbInv->amount, 0, -2);

                // add data to array to be displayed on page
                $investments[] = $dbInv;

                // remember that we have info for this account
                // NOTE: data was sorted by most recent first, so the most recent data is saved.
                $accountsIncluded[] = $dbInv->account;
            }
        }

        return view('investmentsindex', ['investments' => $investments]);

    }   // end of function investmentsindex


    // See how much in each bucket and relevant info
    public function buckets() {

        // retrieve bucket info for goal dates in the past
        function getPastGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $pastGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->where('goalDate', '<=', Carbon::now())
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();
    
            DB::statement("SET sql_mode=only_full_group_by");
            // only keep 2 decimal places (should have 4)
            foreach($pastGoalDateBuckets as $idx=>$bucket) {
                $pastGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $pastGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $pastGoalDateBuckets;
        }

        // retrieve bucket info for goal dates in the future
        function getFutureGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $futureGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->whereDate('goalDate', '>', Carbon::today())
                ->whereNotNull('goalDate')
                ->whereYear('goalDate', '=', Carbon::now()->year)
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();
    
            DB::statement("SET sql_mode=only_full_group_by"); 
            // only keep 2 decimal places (should have 4)
            foreach($futureGoalDateBuckets as $idx=>$bucket) {
                $futureGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $futureGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $futureGoalDateBuckets;
        }

        // retrieve bucket info - no goal dates
        function getNoGoalDateBuckets() {
            DB::statement("SET sql_mode = ''");
            $noGoalDateBuckets = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->whereNull('goalDate')
                ->select(
                    'bucketgoals.bucket',
                    'bucketgoals.goalAmount',
                    DB::raw('SUM(transactions.amount) as balance'),
                    DB::raw('(bucketgoals.goalAmount - SUM(transactions.amount)) as NEEDED'),
                    'bucketgoals.goalDate',
                    'bucketgoals.notes'
                )
                ->groupBy('bucketgoals.bucket')
                ->orderBy('goalDate')
                ->get()->toArray();
    
            DB::statement("SET sql_mode=only_full_group_by"); 
            // only keep 2 decimal places (should have 4)
            foreach($noGoalDateBuckets as $idx=>$bucket) {
                $noGoalDateBuckets[$idx]->balance = substr($bucket->balance, 0, -2);
                $noGoalDateBuckets[$idx]->NEEDED = substr($bucket->NEEDED, 0, -2);
            }

            return $noGoalDateBuckets;
        }

        // retrieve total bucket info for all goal dates (to compare with transactionsBucketBalance)
        // sum of money in all the buckets
        function getTotalBucketBalance() {

            $totalBucketBalance = DB::table('bucketgoals')
                ->leftJoin('transactions', function($join) {
                    $join->on('bucketgoals.bucket', '=', 'transactions.bucket');
                })
                ->select(
                    DB::raw('SUM(transactions.amount) as balance'),
                )
                ->get()->toArray();

            // only keep 2 decimal places (should have 4)
            $totalBucketBalance = substr($totalBucketBalance[0]->balance, 0, -2);
            
            return $totalBucketBalance;
        }


        // retrieve balance in transactions for buckets (should match totalBucketBalance)
        // sum of all transactions with buckets 
        // NOTE: ONLY Disc Svgs transactions should have buckets, and ALL Disc Svgs transactions should have buckets.
        function getTransactionsBucketBalance() {
            $transactionsBucketBalance = DB::table('transactions')
                ->where("account", "DiscSavings")
                ->whereIn("bucket", [
                    'BigItems', 'College', 'CC', 'Holiday', 'Insurance', 'LTC', 'Misc', 'PropertyTax', 'RetSavings', 'Vacation', 'Water'
                ])
                ->select(
                    DB::raw('SUM(amount) as balance')
                )
                ->get()->toArray();

            // only keep 2 decimal places (should have 4)
            $transactionsBucketBalance = substr($transactionsBucketBalance[0]->balance, 0, -2);
       
            return $transactionsBucketBalance;
        }


        // retrieve bucket info for goal dates in the past
        $pastGoalDateBuckets = getPastGoalDateBuckets();
        
        // retrieve bucket info for goal dates in the future
        $futureGoalDateBuckets = getFutureGoalDateBuckets();

        // retrieve bucket info - no goal dates
        $noGoalDateBuckets = getNoGoalDateBuckets();

        // retrieve total bucket info for all goal dates (to compare with transactionsBucketBalance)
        // sum of money in all the buckets
        $totalBucketBalance = getTotalBucketBalance();
        
        // retrieve balance in transactions for buckets (should match totalBucketBalance)
        // sum of all transactions with buckets 
        // NOTE: ONLY Disc Svgs transactions should have buckets, and ALL Disc Svgs transactions should have buckets.
        $transactionsBucketBalance = getTransactionsBucketBalance();

        // show page with buckets and amounts; shows warning if totals don't match
        return view('buckets', 
            [
                'pastGoalDateBuckets' => $pastGoalDateBuckets, 
                'futureGoalDateBuckets' => $futureGoalDateBuckets, 
                'noGoalDateBuckets' => $noGoalDateBuckets, 
                'totalBucketBalance' => $totalBucketBalance, 
                'transactionsBucketBalance' => $transactionsBucketBalance
            ]
        );

    }   // end of function buckets


    // Init view to move funds between buckets
    public function moveBuckets() {

        // show page with buckets and amounts; shows warning if totals don't match
        return view('moveBuckets');

    }   // end of function moveBuckets


    // Write the transaction records to move the funds between the buckets
    public function moveFundsBetweenBuckets(Request $request) {

        // info needed from request
        $fromBucket = $request->input("fromBucket");
        $toBucket = $request->input("toBucket");
        $moveAmount = $request->input("moveBucketAmount");

        // other calc'd data needed
        $today = date('Y-m-d');
        $stmtDate = substr($today, 2, 2) . "-" . date('M');
        $halfAmt = .5 * $moveAmount;
        $note = "move from " . $fromBucket . " to " . $toBucket;

        // create from transaction
        $fromTransaction = [];
        $fromTransaction['trans_date'] = $today;
        $fromTransaction['clear_date'] = $today;
        $fromTransaction['account'] = "DiscSavings";
        $fromTransaction['toFrom'] = "Move Between Buckets";
        $fromTransaction['amount'] = -$moveAmount;
        $fromTransaction['amtMike'] = -$halfAmt;
        $fromTransaction['amtMaura'] = -$halfAmt;
        $fromTransaction['category'] = "BucketMove";
        $fromTransaction['stmtDate'] = $stmtDate;
        $fromTransaction['total_amt'] = 0;
        $fromTransaction['total_key'] = "move";
        $fromTransaction['bucket'] = $fromBucket;
        $fromTransaction['notes'] = $note;

        // write from transaction
        $newId = DB::table('transactions')->insertGetId($fromTransaction);
        // update total_key with newId
        DB::table('transactions')
            ->where('id', $newId)
            ->update(['total_key' => $newId]);

        // creat to transaction
        $toTransaction = [];
        $toTransaction['trans_date'] = $today;
        $toTransaction['clear_date'] = $today;
        $toTransaction['account'] = "DiscSavings";
        $toTransaction['toFrom'] = "Move Between Buckets";
        $toTransaction['amount'] = $moveAmount;
        $toTransaction['amtMike'] = $halfAmt;
        $toTransaction['amtMaura'] = $halfAmt;
        $toTransaction['category'] = "BucketMove";
        $toTransaction['stmtDate'] = $stmtDate;
        $toTransaction['total_amt'] = 0;
        $toTransaction['total_key'] = $newId;
        $toTransaction['bucket'] = $toBucket;
        $toTransaction['notes'] = "move from " . $fromBucket . " to " . $toBucket;

        // write the "to" transaction
        $result = DB::table('transactions')->insert($toTransaction);

        // reload buckets page
        return redirect()->route('buckets');

    }   // end of function moveFUndsBetweenBuckets


    // get budget categories from categories table
    // IorE:
    //  "I" for income categories
    //  "E" for expense categories
    function getCategories($IorE) {
        $categories = DB::table('categories')
            ->where('ie', '=', $IorE)
            ->select('name')
            ->orderBy('name')
            ->get()->toArray();

            $categories = array_column($categories, "name");

        return $categories;
    }


    public function getBudgetData($year) {
        $budgetRecords = DB::table("budget")
            ->where("year", $year)
            ->get()->toArray();

        $budgetData = [];
        foreach($budgetRecords as $record) {
            $arrayRecord = (array)$record;
            $budgetData[$arrayRecord['category']] = 
                array_diff_key($arrayRecord, array_flip(['id', 'year' , 'category']));            
        }

        return $budgetData;
    }   // end function getBudgetData


    public function getNotes($typeOfNote, $year) {

        $notes = [];

        $db_notes = DB::table("notes")
            ->where("type_of_note", $typeOfNote)
            ->whereBetween("trans_date", [$year . "-01-01", $year . "-12-31"])
            ->get()->toArray();

        foreach($db_notes as $note) {

            // create new element for this category in notes, if needed
            if(!array_key_exists($note->category, $notes)) {
                $notes[$note->category] = '';
            }

            // create new element for current month, if needed
            // get month
            $noteMonth = date("M", strtotime($note->trans_date));
           
            // Append note to any existing notes
            if($note->amount == null) $amountPhrase = '';
            else $amountPhrase = " ($" . $note->amount . ")";
            $notes[$note->category] .= $noteMonth . ": " . $note->note . $amountPhrase . ".  ";
        }
 
        return $notes;
    }   // end function getNotes


    // see spending transactions
    public function spending() {

        $routeName = Route::currentRouteName();
        $who = str_replace("spending", "", $routeName);
        $who = strtolower($who);
        
        // will need to identify month by number
        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get transactions for the current year
        $thisYear = date('Y');

        // make sure who has only the first letter capitalized (to match database)
        $who = strtoupper(substr($who, 0, 1)) . strtolower(substr($who, 1));
      
        // get category
        $category = $who . "Spending";

        // get spending transactions
        // - include household transactions credited to the spending budget
        // - and all transactions in/out of spending savings account
        $spendingTransactions = DB::table("transactions")
            ->select([
                'trans_date',
                DB::raw('YEAR(trans_date) as year'),
                DB::raw('MONTH(trans_date) as month'),
                'clear_date',
                'account',
                'toFrom',
                'amount',
                'total_amt',
                'notes',
                'category'
            ])
            ->where('trans_date', '>=', $thisYear . "-01-01")
            ->where(function ($query) use ($category, $who) {
                $query->where('category', $category)
                        //  Mike has only account "Mike"
                        //  Maura has "MauraSCU" and "MauraDisc"
                      ->orWhere('account', 'like', $who . '%');
            })
            ->orderBy('trans_date')
            ->orderBy('toFrom')
            ->orderBy('amount')
            ->get()
            ->toArray();

        // get spending budget
        $monthlySpendingBudget = DB::table('budget')
            ->where("year", $thisYear)
            ->where("category", $category)
            ->get()
            ->toArray();

        // add budget transaction at beginning of each month.  Use $month to see month change.
        $month = '';

        // keep track of spending left in budget
        $remainingSpendingBudget = 0;

        // new array to send to view (with monthly spending budget lines added)
        $viewTransactions = [];

        // insert lines to add budget; and keep running balance of spending budget remaining
        foreach($spendingTransactions as $key=>$transaction) {

            // get this transaction's month
            $transactionMonthIdx = $transaction->month-1;
            $transactionMonth = $months[$transactionMonthIdx];

            // if month has changed, insert budget transaction
            if($month != $transactionMonth) {
                // change to new month
                $month = $months[$transactionMonthIdx];

                // calc remaining spending budget
                // note: amt is neg in budget table.  Needs to be subtracted to ADD it to the balance.
                $remainingSpendingBudget -= $monthlySpendingBudget[0]->$month;
            
                // build new budget record
                $budgetRecord = (object)[
                    "trans_date" => $thisYear . "-" . str_pad($transaction->month, 2, '0', STR_PAD_LEFT) . "-01",
                    "year" => $thisYear,
                    "month" => $transaction->month,
                    "clear_date" => $thisYear . "-" . str_pad($transaction->month, 2, '0', STR_PAD_LEFT) . "-01",
                    "account" => "budget",
                    "toFrom" => $month . " budget",
                    "amount" => $monthlySpendingBudget[0]->$month,
                    "total_amt" => "",
                    "notes" => $month . " budget",
                    "category" => $category,
                    "remainingSpendingBudget" => $remainingSpendingBudget
                ];
                $viewTransactions[] = $budgetRecord;
            }

            // update spending left, if appropriate
            // and include balance in transaction for view
            if($transaction->category == $category) {

                // update remaining spending budget
                $remainingSpendingBudget += $transaction->amount;

                // append remaining spending budget to current transaction
                $transaction->remainingSpendingBudget = $remainingSpendingBudget;
            } else {
                // append remaining spending budget to current transaction
                // NOTE: value doesn't change if it's to/from Mike/Maura's savings acct (no category)
                $transaction->remainingSpendingBudget = $remainingSpendingBudget;

            }

            // push transaction to viewTransactions
            $viewTransactions[] = $transaction;

        }

        // return view to display spending records
        return view('spending', ['who' => $who, 'spendingTransactions' => $viewTransactions]);
    
    }   // end function spending


    // get car info for trips blade
    public function getCarInfo() {
        // get cars, drivers, and most recent mileage from carcostdetails table
        $DBCarsDriversMileages = DB::table('carcostdetails')
            ->where('key', 'Driver')
            ->orWhere('key', 'like', 'Mileage%')
            ->get()->toArray();
            
        // Transform the data
        $carsDriversMileages = [];
        foreach ($DBCarsDriversMileages as $item) {
            // get which car the data is about
            $car = $item->car;

            // is an element initialized for this car?
            if (!isset($carsDriversMileages[$car])) {
                $carsDriversMileages[$car] = ['car' => $car];
            }
            
            // if it's the default driver, capture that
            if ($item->key === 'Driver') {
                $carsDriversMileages[$car]['Driver'] = $item->value;

            // if it's the mileage, handle that
            } elseif (strpos($item->key, 'Mileage') === 0) {
                // get the mileage and the date
                $odomDate = substr($item->key, 7);
                $mileage = (int)$item->value;

                // if this mileage is higher, capture it and the date
                if (!isset($carsDriversMileages[$car]['Mileage']) || $mileage > $carsDriversMileages[$car]['Mileage']) {
                    $carsDriversMileages[$car]['Mileage'] = $mileage;
                    $carsDriversMileages[$car]['OdomDate'] = $odomDate;
                }
            }
        }

        // Convert to array of objects
        $carInfo = array_values($carsDriversMileages);
        // error_log("carInfo:");
        // error_log(json_encode($carInfo));

        return $carInfo;
    }   // end of function getCarInfo


    // get array of trip names
    public function getTripNames() {

        // get an array of all the trip names
        $tripNames = DB::table('trips')
            ->pluck('trip');
        // error_log("tripnames:");
        // foreach($DBtripNames as $tripName) error_log($tripName);   
        
        return $tripNames;
    }

    // calc cost to use car for a trip
    // and write corresponding records to transactions table
    public function trips() {

        // get cars, drivers, and most recent mileage from carcostdetails table
        $carInfo = $this->getCarInfo();

        // get an array of all the trip names
        $tripNames = $this->getTripNames();

        // return view to get info to calc cost to use car for a trip
        return view('trips', ['carInfo' => $carInfo, 'tripNames' => $tripNames]);
    
    }   // end function trips

    
    // See budget info
    public function budget(Request $request) {
        
        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get budget data
        $budgetData = $this->getBudgetData($year);
        
        // budget page
        return view('budget', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'budgetData' => $budgetData
            ]
        );

    }   // end of function budget


    // See budget vs actual info
    public function budgetactuals(Request $request) {
        
        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get budget data
        $budgetData = $this->getBudgetData($year);

        // format with commas and to 2 decimal places
        foreach($budgetData as $category=>$data) {
            $budgetData[$category]['total'] = number_format((float)$data['total'], 2);
            foreach($months as $month) {
                $budgetData[$category][$month] = number_format((float)$data[$month], 2);
            }
        }

        // get actuals data
        [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals] =
            $this->getActualsData($year, $months, $incomeCategories, $expenseCategories);

        // get notes
        $notes = $this->getNotes("b_vs_a", $year);

        // budgetactuals page
        return view('budgetactuals', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'budgetData' => $budgetData,
                'actualIncomeData' => $actualIncomeData,
                'actualExpenseData' => $actualExpenseData,
                'actualIncomeTotals' => $actualIncomeTotals,
                'actualExpenseTotals' => $actualExpenseTotals,
                'actualGrandTotals' => $actualGrandTotals,
                'notes' => $notes
            ]
        );

    }   // end of function budgetactuals


    public function getActualsData($year, $months, $incomeCategories, $expenseCategories) {

        $actualsTransactions = DB::table("transactions")
            ->whereBetween("trans_date", [$year . "-01-01", $year . "-12-31"])
            ->get()->toArray();

        // init actual income and expense data
        $actualIncomeData = [];
        foreach($incomeCategories as $category) {
            $actualIncomeData[$category] = [
                "january" => 0,
                "february" => 0,
                "march" => 0,
                "april" => 0,
                "may" => 0,
                "june" => 0,
                "july" => 0,
                "august" => 0,
                "september" => 0,
                "october" => 0,
                "november" => 0,
                "december" => 0,
                "total" => 0
            ];
        }

        $actualExpenseData = [];
        foreach($expenseCategories as $category) {
            $actualExpenseData[$category] = [
                "january" => 0,
                "february" => 0,
                "march" => 0,
                "april" => 0,
                "may" => 0,
                "june" => 0,
                "july" => 0,
                "august" => 0,
                "september" => 0,
                "october" => 0,
                "november" => 0,
                "december" => 0,
                "total" => 0
            ];
        }

        // sum actuals by month and category
        foreach($actualsTransactions as $actualsTransaction) {
            $thisTransMonth = substr($actualsTransaction->trans_date, 5, 2);
            $thisMonth = $months[(int)$thisTransMonth-1];
            if(in_array($actualsTransaction->category, $incomeCategories)) {
                $actualIncomeData[$actualsTransaction->category][$thisMonth] += $actualsTransaction->amount;
                $actualIncomeData[$actualsTransaction->category]['total'] += $actualsTransaction->amount;
            } else if(in_array($actualsTransaction->category, $expenseCategories)) {
                $actualExpenseData[$actualsTransaction->category][$thisMonth] += $actualsTransaction->amount;                
                $actualExpenseData[$actualsTransaction->category]['total'] += $actualsTransaction->amount;                
            }
        }

        // calc actualIncomeTotals, actualExpenseTotals, actualGrandTotals
        foreach ($actualIncomeData as $category => $catMonths) {
            foreach ($catMonths as $month => $amount) {
                if (!isset($actualIncomeTotals[$month])) $actualIncomeTotals[$month] = 0;
                // remove commas before converting string to float
                $actualIncomeTotals[$month] += (float)(str_replace(',', '', $amount));
            }
        }
        foreach ($actualExpenseData as $category => $catMonths) {
            foreach ($catMonths as $month => $amount) {
                if (!isset($actualExpenseTotals[$month])) {
                    $actualExpenseTotals[$month] = 0;
                }
                // remove commas before converting string to float
                $actualExpenseTotals[$month] += (float)(str_replace(',', '', $amount));
            }
        }

        // calc grand totals for each month
        foreach ($months as $monthIdx => $catMonth) {
            $actualGrandTotals[$catMonth] = $actualIncomeTotals[$catMonth] + $actualExpenseTotals[$catMonth];
            // remove commas before converting string to float
            $actualGrandTotals[$catMonth] = number_format($actualGrandTotals[$catMonth], 2);
        }
        // calc total grand total
        $actualGrandTotals['total'] = $actualIncomeTotals['total'] + $actualExpenseTotals['total'];
        // ...and format
        $actualGrandTotals['total'] = number_format($actualGrandTotals['total'], 2);


        // format all number to 2 decimal places
        foreach($actualIncomeData as $categoryKey=>$category) {
            foreach($months as $month) {
                $actualIncomeData[$categoryKey][$month] = number_format($actualIncomeData[$categoryKey][$month], 2);
            }
            $actualIncomeData[$categoryKey]['total'] = number_format($actualIncomeData[$categoryKey]['total'], 2);
        }

        foreach($actualExpenseData as $categoryKey=>$category) {
            foreach($months as $month) {
                $actualExpenseData[$categoryKey][$month] = number_format($actualExpenseData[$categoryKey][$month], 2);
            }
            $actualExpenseData[$categoryKey]['total'] = number_format($actualExpenseData[$categoryKey]['total'], 2);
        }

        foreach($actualIncomeTotals as $idx=>$total) $actualIncomeTotals[$idx] = number_format($total, 2);

        foreach($actualExpenseTotals as $idx=>$total) $actualExpenseTotals[$idx] = number_format($total, 2);

        return [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals];
    }


    // See actuals info
    public function actuals(Request $request) {

        // get year from payload; default to current year if not in payload
        $year = $request->year;
        if($year == null) $year = date('Y');

        $months = [
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december'
        ];

        // get income & expense categories
        $incomeCategories = $this->getCategories("I");
        $expenseCategories = $this->getCategories("E");

        // get actuals data
        [$actualIncomeData, $actualExpenseData, $actualIncomeTotals, $actualExpenseTotals, $actualGrandTotals] =
            $this->getActualsData($year, $months, $incomeCategories, $expenseCategories);

        // actuals page
        return view('actuals', 
            [
                'year' => $year, 
                'incomeCategories' => $incomeCategories, 
                'expenseCategories' => $expenseCategories,
                'actualIncomeData' => $actualIncomeData,
                'actualExpenseData' => $actualExpenseData,
                'incomeTotals' => $actualIncomeTotals,
                'expenseTotals' => $actualExpenseTotals,
                'grandTotals' => $actualGrandTotals
            ]
        );

    }   // end of function actuals


    // This was used to eliminate MMSpending transactions, so shouldn't be needed again.
    // transactions table was altered to no longer allow "MMSpending" as a category.
    // split each MMSpending transaction into a MauraSpending and MikeSpending
    public function splitMandM() {

        DB::transaction(function () {
            // get all MMSpending transactions
            $mmTransactions = DB::select('SELECT * FROM transactions WHERE category = "MMSpending"');

            // for each transaction, change the existing one to the MikeCategory
            //      with amount cut in half
            //      amtMike stays the same
            //      amtMaura is 0
            //      category changed to "MikeSpending"
            // and create a new transaction, the same as the original with the following changes:
            //      new id
            //      amount cut in half
            //      amtMaura stays the same (same as amtMike)
            //      amtMike is 0
            //      category changed to "MauraSpending"
            // If there's no total_key, for both the changed original record and the new record...
            //      total_key = id of the original transaction
            //      total_amt = amount of the original transaction
            foreach($mmTransactions as $transaction) {
                try {
                    // save the id to use as the total_key
                    $id = $transaction->id;
                    
                    // keep existing total_amt and total_key
                    // otherwise set to total_amt = original amount, and total_key = original id
                    if($transaction->total_key == null) {
                        $total_key = (string)($id);
                        $total_amt = floatval($transaction->amount);
                    } else {
                        $total_key = $transaction->total_key;
                        $total_amt = $transaction->total_amt;
                    }
                    // handle total_key that includes other transactions
    
                    // change transaction to MikeSpending part & update
                    DB::table('transactions')
                    ->where('id', '=', $id)
                    ->update([
                        'total_key' => $total_key,
                        'total_amt' => $total_amt,
                        'amount' => DB::raw('CAST(amount / 2 AS FLOAT)'),
                        'amtMaura' => 0,
                        'category' => 'MikeSpending'
                    ]);
                
                    
                    // insert new MauraSpending transaction
                    DB::insert('INSERT INTO transactions (
                        trans_date,
                        clear_date,
                        account,
                        toFrom,
                        total_key, 
                        total_amt, 
                        amount, 
                        amtMike,
                        amtMaura, 
                        method,
                        category,
                        tracking,
                        stmtDate,
                        bucket,
                        notes,
                        lastBalanced
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                        $transaction->trans_date,
                        $transaction->clear_date,
                        $transaction->account,
                        $transaction->toFrom,
                        $total_key,
                        $total_amt,
                        ($transaction->amount ?? 0) / 2,    // amount
                        0,                                  // amtMike
                        $transaction->amtMaura,
                        $transaction->method,
                        'MauraSpending',                    // category
                        $transaction->tracking,
                        $transaction->stmtDate,
                        $transaction->bucket,
                        $transaction->notes,
                        $transaction->lastBalanced
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Error splitting M & M Spending: " . $e->getMessage());
                    throw $e;
                }    
            }

        });

        return "Done split M and M.";
    }

}
