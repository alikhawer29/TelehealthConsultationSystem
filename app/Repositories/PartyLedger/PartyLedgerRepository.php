<?php

namespace App\Repositories\PartyLedger;

use App\Models\Media;
use App\Core\Traits\SplitPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\PartyLedgerAccountReference;
use App\Core\Abstracts\Repository\BaseRepository;

class PartyLedgerRepository extends BaseRepository implements PartyLedgerRepositoryContract
{

    protected $model;
    use SplitPayment;
    protected $numericRangeMax = 999999;
    protected $startingCode = 100000;
    protected $startingLetter = 'A';
    protected $maxLetter = 'Z';


    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Generate a new account code.
     *
     * @return string
     * @throws Exception
     */
    /**
     * Generate a new account code.
     *
     * @return string
     * @throws Exception
     */
    public function generateAccountCode(): string
    {
        try {
            // Retrieve the last account code from the database, ordered by account_code descending
            $lastAccountCode = $this->model->latest('account_code')->first();

            // If no account code exists yet, start from 100000
            if (!$lastAccountCode) {
                return (string)$this->startingCode;
            }

            // Get the last account code
            $lastCode = $lastAccountCode->account_code;

            // Check if the last account code contains letters
            if (preg_match('/^([A-Z]+)(\d+)$/', $lastCode, $matches)) {
                // Extract the letter(s) and numeric part
                $letter = $matches[1];
                $lastNumericCode = (int)$matches[2];
            } else {
                // If no letter is present, it's purely numeric
                $lastNumericCode = (int)$lastCode;
                $letter = ''; // No letter
            }

            // Check if the numeric part has reached the max limit for the current letter
            if ($lastNumericCode >= $this->numericRangeMax) {
                // If the numeric range is exhausted, move to the next letter
                if ($letter === $this->maxLetter) {
                    throw new \Exception("Account code limit reached. No more codes available.");
                }
                // Increment the letter and reset the numeric code to the starting value
                $letter = $this->incrementLetter($letter);
                $lastNumericCode = $this->startingCode;
            }

            // Generate the new account code
            return $letter . str_pad($lastNumericCode + 1, 6, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            throw new \Exception('Error generating account code: ' . $e->getMessage());
        }
    }

    /**
     * Increment the letter part of the account code.
     *
     * @param string $letter
     * @return string
     */
    private function incrementLetter(string $letter): string
    {
        // Increment the letter by one
        if ($letter === '') {
            return $this->startingLetter;
        }

        // Get the next letter
        $nextLetter = chr(ord($letter) + 1);

        return $nextLetter;
    }

    public function get()
    {
        try {
            $data =   $this->model->get();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function status($id)
    {
        try {
            $user = request()->user();
            $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END")
                ]);

            return true;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }



    public function create(array $params)
    {
        DB::beginTransaction();

        try {

            $user = request()->user();
            $parent_id = $user->role === 'employee' ? $user->parent_id : $user->id;
            $branch_id = $user->selected_branch;

            // Check if the account code type for the parent is 'auto'
            $accountCodeType = PartyLedgerAccountReference::where('user_id', $parent_id)->value('account_code');

            $data = array_merge($params, [
                'branch_id' => $branch_id,
                'created_by' => $user->id,
                'parent_id' => $parent_id,
            ]);

            // Add account code if the type is 'auto'
            if ($accountCodeType === 'auto') {
                $data['account_code'] = $this->generateAccountCode();
            }

            // Create the record using the model
            $result = $this->model->create($data);

            // Commit the transaction
            DB::commit();

            // Return the result of the creation
            return $result;
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Rethrow the exception to be handled by the caller
            throw $th;
        }
    }


    public function updateClassification(array $params)
    {
        DB::beginTransaction();

        try {

            $user = request()->user();
            $parent_id = $user->role === 'employee' ? $user->parent_id : $user->id;

            $data = array_merge($params, [
                'edited_by' => $user->id,
                'parent_id' => $parent_id,
            ]);

            $result = $this->model->create($data);

            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function attachments($params, $id)
    {
        \DB::beginTransaction();
        try {

            $data = $this->model->where('id', $id)->exists();

            if (!$data) {
                throw new \Exception('Record not found'); // Custom error message
            }

            if (isset($params['files'])) {
                foreach ($params['files'] as $file) {
                    $this->uploadAttachment($file, $id, get_class($this->model));
                }
            }

            \DB::commit();
            return true;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    protected function uploadAttachment($file, $id, $type)
    {
        // Upload and store the file in a single step
        $path = Storage::putFile('public/media', $file);

        // Store file details in the Media model
        Media::create([
            'path' => basename($path),
            'field_name' => 'file',
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $type,
            'fileable_id' => $id,
        ]);
    }
}
