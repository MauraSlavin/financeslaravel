<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $beginDate = date('Y-m-d', strtotime('-1 month'));
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
        $ignore = DB::table("toFromAliases")
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
            $toFromAlias = DB::table("toFromAliases")
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
        $accounts[] = $all;

        return view('accounts', ['accounts' => $accounts, 'acctsMsg' => $acctsMsg]);
    }

    function calcSplitTotals($transactions) {

        // get splits that might not be in transactions
        $splitKeys = array_column($transactions, 'total_keys');
        // left off here
        error_log("splitKeys:");
        foreach($splitKeys as $splitKey) error_log($splitKey);
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

        // get toFromAliases (auto converts what the "bank" uses to what's in the database)
        $toFromAliases = DB::table("toFromAliases")
            ->get()->toArray();
        $toFromAliases = str_replace(" ", "%20", json_encode($toFromAliases));
        // error_log("\ntoFromAliases:");
        // foreach($toFromAliases as $thisOne) error_log(" - " . json_encode($thisOne));

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
        $firstDay = $thisYear . "-01-01";

        // get amount spent for this category this year
        $spentTotals = DB::table('transactions')
            ->selectRaw('category, SUM(amount) as spent')
            ->where('trans_date', '>=', $firstDay)
            ->groupBy('category')
            ->get()
            ->toArray();

        // get year-to-month budgets by category (budget this year up to and including this month)
        $ytmBudgets = DB::table('newBudget')
            ->selectRaw('year, category, SUM(budgetAmount) as total_budget')
            ->where('monthNum', '<=', $thisMonth)
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()
            ->toArray();

        // get full year budgets by category
        $yearBudgets = DB::table('newBudget')
            ->selectRaw('year, category, SUM(budgetAmount) as total_budget')
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()
            ->toArray();

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

        return view('transactions', ['accountName' => $accountName, 'newTransactions' => [], 'transactions' => $transactions, 'beginDate' => $beginDate, 'endDate' => $endDate, 'accountNames' => $accountNames, 'accountIds' => $accountIds, 'lastStmtDates' => $lastStmtDates, 'toFroms' => $toFroms, 'toFromAliases' => $toFromAliases, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => false, 'clearedBalance' => $clearedBalance, 'registerBalance' => $registerBalance, 'lastBalanced' => $lastBalanced]);
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


    // reads the csv file, messages, and writes to transactions database
    // Right now (10/2/24), $account has to be "disccc".  
    //      - modify so it can be anything in a new table (accounts?) that defines how the records are built for each account
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

        // get all previously used toFrom values
        $toFroms = DB::table("transactions")
            ->distinct()->get("toFrom")->toArray();
        $toFroms = array_column($toFroms, 'toFrom');
        $toFroms = str_replace(" ", "%20", json_encode($toFroms));
        
        // get toFromAliases (auto converts what the "bank" uses to what's in the database)
        $toFromAliases = DB::table("toFromAliases")
            ->get()->toArray();
        $toFromAliases = str_replace(" ", "%20", json_encode($toFromAliases));

        // get all the defined account names
        $accountNames = array_column($accounts, 'accountName');

        // if accountName not in accounts, it's not a valid accountName
        if(!in_array($accountName, $accountNames)) {
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
        $ytmBudgets = DB::table('newBudget')
            ->selectRaw('year, category, SUM(budgetAmount) as total_budget')
            ->where('monthNum', '<=', $thisMonth)
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()
            ->toArray();

        // get full year budgets by category
        $yearBudgets = DB::table('newBudget')
            ->selectRaw('year, category, SUM(budgetAmount) as total_budget')
            ->where('year', $thisYear)
            ->groupBy('year', 'category')
            ->get()
            ->toArray();

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

        // TO DO:  order transactions by trans_date descending, toFrom ascending

        // error_log(json_encode($newCsvData));
        return view('transactions', ['accountName' => $accountName, 'newTransactions' => $newTransactions, 'transactions' => $transactions, 'accountNames' => $accountNames, 'toFromAliases' => $toFromAliases, 'toFroms' => $toFroms, 'categories' => $categories, 'trackings' => $trackings, 'buckets' => $buckets, 'upload' => true, 'beginDate' => $beginDate, 'endDate' => $endDate, 'clearedBalance' => '', 'registerBalance' => '', 'lastBalanced' => '']);
    }


    // delete a transaction by id
    public function delete($id)
    {
        try {
            $transaction = DB::table('transactions')
                ->where("id", $id)
                ->delete();
            return response()->json([
                'message' => 'Record deleted successfully',
                'status' => 'success'
            ]);

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
            error_log("response after update (id: " . $id . "): ");
            error_log(json_encode($response));

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


    // insert a new toFromAlias record
    public function insertAlias($origToFrom, $newValue) 
    {

        $origToFrom = urldecode($origToFrom);
        $newValue = urldecode($newValue);

        try {
            
            $response = DB::table('toFromAliases')
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
            error_log("\nProblem inserting toFromAliases record for origToFrom: " . $origToFrom . " and transToFrom: " . $newValue);
            error_log(json_encode(['exception' => $e]));
            \Log::error('Error inserting toFromAliases record.  ' . $e->getMessage());
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

            $defaults = DB::table('toFromAliases')
                ->where('account_id', $accountId)
                ->whereRaw("LOWER(transToFrom) = ?", [$lowerToFrom])
                ->first();

            // if none found, look in origToFrom
            if($defaults == null) {
                $defaults = DB::table('toFromAliases')
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
            $grossPay = $request->input('gbnetpay') + $request->input('gbtaxwh');
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

        // transaction for tax withheld from deposit to checking
        function writeGBtaxWH($request, $payId) {
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

        // transaction for tax withheld from deposit to checking
        writeGBtaxWH($request, $payId);

        // write spending transactions for Mike (checking to spending)
        writeGBspending($request, "Mike");

        // write spending transactions for Maura (checking to spending)
        writeGBspending($request, "MauraSCU");

        // go back to accounts page, with reminder wrt transfer
        $reminder = "REMEMBER to transfer " . $request->input('gbspending') . " each to Mike's and Maura's spending accounts.";
        return redirect()->route('accounts')->with('acctsMsg', $reminder);
    }   // end of writeGBLimo
    

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

        // order inv accounts alphabetically
        usort($invAccounts, function($a, $b) {
            return strcmp(strtolower($a->account), strtolower($b->account));
        });

        // get transaction with latest clear_date for each account
        $invAccounts = getLatest($invAccounts, $invAccountNames);

        // merge the accounts arrays
        $accounts = array_merge($transAccounts, $invAccounts);

        // add Total line to $accounts
        $accounts[] = getTotalAssets($accounts);

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
