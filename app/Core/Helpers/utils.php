<?php

use App\Models\Admin;
use Firebase\JWT\JWT;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\Request;
use App\Models\Quotation;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;

function collectionPagination($packages, $paginatedData)
{
    return [
        'data' => $packages,
        'pagination' => [
            'total' => $paginatedData->total(),
            'per_page' => $paginatedData->perPage(),
            'current_page' => $paginatedData->currentPage(),
            'last_page' => $paginatedData->lastPage(),
            'from' => $paginatedData->firstItem(),
            'to' => $paginatedData->lastItem(),
        ],
    ];
}

function applyRounding(float $amount): float
{
    // Retrieve rounding preference directly, default to no rounding
    $roundingOff = Branch::where('id', auth()->user()->selected_branch)
        ->value('rounding_off');

    return $roundingOff === 1 ? round($amount, 0) : $amount;
}



function generateTicketID($start = 0, $length = 12)
{
    // Generate ticket ID based on max ID and padding
    // return 'RFQ-' . str_pad((string) Quotation::max('id') + 1, 2, '0', STR_PAD_LEFT);

    // Get the max ID from Quotation, default to 0 if null
    $maxId = Appointment::max('id') ?? 0;

    // Generate ticket ID based on max ID and padding
    return str_pad($maxId + 1, 2, '0', STR_PAD_LEFT);
}

function tinyUrl($url)
{
    // $tiny = 'http://tinyurl.com/api-create.php?url='.$url;
    // return file_get_contents($tiny);
    return $url;
}

function tinyUrlTesting($url)
{
    $tiny = 'http://tinyurl.com/api-create.php?url=';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tiny . urlencode(trim($url)));
    $response = curl_exec($ch);
    curl_close($ch);
}
function curl_get_file_contents($URL)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);

    if ($contents) return $contents;
    else return FALSE;
}
function prepare_text($text, $data = [])
{

    return str_replace(array_keys($data), array_values($data), $text);
}

function randomSupportAdmin($identifier = 'supports.internal.actions', $exclude = [])
{
    return Admin::query()->where('status', 1)->inRandomOrder()->whereRaw('role IN
        (
        SELECT
            role_id
        FROM
            role_permissions
        WHERE
        permission_id IN
        (
        SELECT
            id
        FROM
            permissions_list
        WHERE
            identifier = ?
        ) AND
        value = ?
    )', ['identifier' => $identifier, 'value' => 1])
        ->where('id', '!=', auth('admin')->id())
        ->when(count($exclude) > 0, function ($q) use ($exclude) {
            $q->whereNotIn('id', $exclude);
        })
        ->first();
}

function frontend_url($string)
{
    return config('app.frontend_url') . '/' . $string;
}

function pendingRequestsCount($userId, $requestId = null)
{
    return Request::whereIn('status', ['pending', 'assigned'])
        ->when(!empty($requestId), function ($q) use ($requestId) {
            $q->where('id', '!=', $requestId);
        })
        ->where(function ($q) use ($userId) {
            $q->where('requested_by', $userId)
                ->orWhere('admin_id', $userId)
                ->orWhere('passed_to', $userId);
        })->count();
}

function getWalletBalance($userId = null)
{
    if (!$userId) {
        $userId = auth()->id();
    }

    $amount = DB::select('SELECT IFNULL((SELECT SUM(amount) FROM wallet_logs WHERE user_id = ? AND type = ?),0) as out_amount, IFNULL((SELECT SUM(amount) FROM wallet_logs WHERE user_id = ? AND type = ?),0) as in_amount', [$userId, 'out', $userId, 'in']);
    $amount = end($amount);
    return abs($amount->in_amount - $amount->out_amount);
}

function getSessionStatus($sessionable_id = null)
{
    if (!$sessionable_id) {
        $sessionable_id = auth()->id();
    }

    $amount = \DB::select(
        'SELECT IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as out_amount,
                                 IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as in_amount',
        [$sessionable_id, 'out', $sessionable_id, 'in']
    );
    $amount = end($amount);
    return $amount  = $amount->out_amount < $amount->in_amount ? 'Active' : 'Expired';
}

function getSessionBalance($sessionable_id = null)
{
    if (!$sessionable_id) {
        $sessionable_id = auth()->id();
    }

    $amount = \DB::select(
        'SELECT IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as out_amount,
                                 IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as in_amount',
        [$sessionable_id, 'out', $sessionable_id, 'in']
    );
    $amount = end($amount);
    return abs($amount->in_amount - $amount->out_amount);
}

function getSessionsIn($sessionable_id = null)
{
    if (!$sessionable_id) {
        $sessionable_id = auth()->id();
    }

    $amount = DB::select('SELECT IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as out_amount, IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as in_amount', [$sessionable_id, 'out', $sessionable_id, 'in']);
    $amount = end($amount);
    return abs($amount->in_amount);
}
function getSessionsOut($sessionable_id = null)
{
    if (!$sessionable_id) {
        $sessionable_id = auth()->id();
    }

    $amount = DB::select('SELECT IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as out_amount, IFNULL((SELECT SUM(sessions) FROM user_sessions WHERE sessionable_id = ? AND type = ?),0) as in_amount', [$sessionable_id, 'out', $sessionable_id, 'in']);
    $amount = end($amount);
    return abs($amount->out_amount);
}

function banner()
{
    $banners = Banner::with('file')->limit(3)->get();

    $bannerImages = $banners->map(function ($banner, $key) {
        return [
            'id' => ++$key,
            'url' => $banner->file ? $banner->file->file_url : "public/assets/images/banner.jpg"
        ];
    });

    return $bannerImages->toArray();
}

function content($type)
{
    if ($type == 'privacy_policy') {
        return "<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>";
    } elseif ($type == 'terms_condition') {
        return "<p class='font-weight-bold text-theme-primary'>A Pursuit Of Finding Forever Homes For Animals!</p><p>At DalPetAdopt, we are passionate about ensuring that pets find their perfect homes. We provide a safe and secure environment for people to adopt or rehome pets needing a loving forever home. With many years of industry expertise, we strive to connect people with shelter animals to bring joy to owners’ and pets’ lives. People can be sure that when they come to us for adoption, they are getting all the necessary information about the pet and its general health, as well as vital tips on how to properly care for it so that it can enjoy a happy life in its new family.</p><p>From private individuals looking to give up their furry friends to those wanting to add an animal companion into their life, we ensure that everyone involved has only the best interests at heart!</p><h4><b>Our Mission</b></h4><p class='font-weight-bold text-theme-primary'>Fostering Animal Love, Promoting Trouble-Free Adoption!</p><p>Our mission is clear to save every single animal!<br />We match interested buyers with the most suitable pet so they can give them a forever home full of love and warmth.</p><h4><b>Our Vision </b></h4><p class='font-weight-bold text-theme-primary'>Making A No-Kill Future A Possibility!</p><p>Our mission is to bring about a world where no animal is ever euthanized. We are fighting the problem of pet overpopulation with compassionate solutions, uniting furry friends in adoptive homes and transforming their lives for the better. We provide love, care, and wellness - striving to create safe havens for pups by offering unconditional acceptance and warmth so they can really thrive!</p><p>We serve pet owners nationwide, offering convenient online donation requests from any registered animal group or home. We make sure all animals have access to the utmost care and safety!</p>";
    } elseif ($type == 'about_us') {
        return "<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>";
    } else {
    }
}

function generateJwt($guestIssuerId, $sharedSecret, $userEmail, $userName)
{
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // 1 hour

    $payload = [
        'sub' => $userEmail,
        'name' => $userName,
        'iss'  => $guestIssuerId,
        'iat'  => $issuedAt,
        'exp'  => $expirationTime,
    ];

    return JWT::encode($payload, $sharedSecret, 'HS256');
}

function others()
{
    $data['skill_level'] = [
        0 => 'Beginner',
        1 => 'Intermediate',
        2 => 'Advanced'
    ];
    $data['planning_to_practice'] = [
        0 => 'Individually',
        1 => 'With a partner',
        2 => 'Large group'
    ];
    $data['want_to_get_coached'] = [
        0 => '1-2 Times a week',
        1 => '3-4 Times a week',
        2 => 'Everyday',
        3 => 'I dont know it yet'
    ];
    $data['specialities'] = [
        0 => 'Game Strategy',
        1 => 'Technique & Footwork',
        2 => 'Single Matches',
        3 => 'Double Matches',
        4 => 'High Performance Players',
        5 => 'Junior',
        6 => 'Adults',
    ];
    $data['sport_playing'] = [
        0 => 'Tennis',
        1 => 'Pickleball'
    ];
    $data['appointment_type'] = [
        0 => 'on-site',
        1 => 'online'
    ];
    $data['bank_account_type'] = [
        0 => 'Saving',
        1 => 'Checking'
    ];

    return $data;
}



function getDistanceQuery($lat, $lng, $as = 'distance')
{
    /*  */
    return \DB::raw("(
		SELECT
		ROUND(
			IFNULL(
				( 3959 * 2 * ASIN(
					SQRT(
						POWER(
							SIN(
								('$lat' - abs(lat)
								) * ( pi() / 180) / 2
							), 2
						)
				+ COS( '$lat' * ( pi() / 180 ) ) * COS( abs(lat) * ( pi() / 180 ) )
				* POWER( SIN( ('$lng' - lng) * (pi() / 180) / 2 ), 2 ) )
				)
			),0)
			)
		) as {$as}");
}

function error_message($type)
{

    if ($type == 1) {
        return 'Slot time can not be less or more then 30 minutes';
    }
}

function getDistance($origLat, $origLng, $destLat, $destLng)
{
    // Check if latitudes and longitudes are valid
    if (empty($origLat) || empty($origLng) || empty($destLat) || empty($destLng)) {
        return ['distance' => null, 'duration' => null, 'error' => 'Invalid latitude or longitude'];
    }
    $origins = $origLat . ',' . $origLng;
    $destinations = $destLat . ',' . $destLng;
    $units = 'metric';
    $mode = 'driving';
    $apiKey = 'AIzaSyCr72b9o3nFv1SXnuYWU0N3DgPP8rCpTtw';  // Use env variable for API key

    $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
        'origins' => $origins,
        'destinations' => $destinations,
        'units' => $units,
        'mode' => $mode,
        'key' => $apiKey,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        if ($data['status'] == 'OK') {
            $distance = $data['rows'][0]['elements'][0]['distance']['text'];
            $duration = $data['rows'][0]['elements'][0]['duration']['text'];
            return ['distance' => $distance, 'duration' => $duration];
        } else {
            return ['distance' => null, 'duration' => null, 'error' => 'Failed to fetch data from API'];
        }
    } else {
        return ['distance' => null, 'duration' => null, 'error' => 'Failed to fetch data'];
    }
}

// Helper function to convert distance to meters
function convertToMeters($distance)
{
    if (str_ends_with($distance, 'km')) {
        return floatval($distance) * 1000;
    } elseif (str_ends_with($distance, 'm')) {
        return floatval($distance);
    }
    return floatval($distance); // Fallback if no unit is provided
}

function convertStatus($status)
{
    $status = str_replace('_', ' ', $status);
    return ucwords($status);
}
