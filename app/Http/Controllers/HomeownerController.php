<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Support\NameParser;

class HomeownerController extends Controller
{
    public function parse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // read CSV (first column is the homeowner value)
        $rows = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (!empty($data) && isset($data[0]) && trim($data[0]) !== '' && strtolower($data[0]) !== 'homeowner') {
                    $rows[] = $data[0];
                }
            }
            fclose($handle);
        }

        $parser = new NameParser();
        $people = [];
        foreach ($rows as $raw) {
            foreach ($parser->splitIntoSegments($raw) as $segment) {
                $people[] = $parser->parseSingleSegment($segment);
            }
        }

        return response()->json($people);
    }
}
