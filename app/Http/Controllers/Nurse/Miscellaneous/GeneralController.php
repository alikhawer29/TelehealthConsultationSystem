<?php

namespace App\Http\Controllers\Nurse\Miscellaneous;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;


class GeneralController extends Controller
{


    public function languages(): JsonResponse
    {
        try {
            $data = [
                ['value' => 'Afrikaans', 'label' => 'Afrikaans'],
                ['value' => 'Albanian', 'label' => 'Albanian'],
                ['value' => 'Amharic', 'label' => 'Amharic'],
                ['value' => 'Arabic', 'label' => 'Arabic'],
                ['value' => 'Armenian', 'label' => 'Armenian'],
                ['value' => 'Assamese', 'label' => 'Assamese'],
                ['value' => 'Azerbaijani', 'label' => 'Azerbaijani'],
                ['value' => 'Basque', 'label' => 'Basque'],
                ['value' => 'Belarusian', 'label' => 'Belarusian'],
                ['value' => 'Bengali', 'label' => 'Bengali'],
                ['value' => 'Bosnian', 'label' => 'Bosnian'],
                ['value' => 'Bulgarian', 'label' => 'Bulgarian'],
                ['value' => 'Burmese', 'label' => 'Burmese'],
                ['value' => 'Catalan', 'label' => 'Catalan'],
                ['value' => 'Cebuano', 'label' => 'Cebuano'],
                ['value' => 'Chichewa', 'label' => 'Chichewa'],
                ['value' => 'Chinese (Simplified)', 'label' => 'Chinese (Simplified)'],
                ['value' => 'Chinese (Traditional)', 'label' => 'Chinese (Traditional)'],
                ['value' => 'Corsican', 'label' => 'Corsican'],
                ['value' => 'Croatian', 'label' => 'Croatian'],
                ['value' => 'Czech', 'label' => 'Czech'],
                ['value' => 'Danish', 'label' => 'Danish'],
                ['value' => 'Dutch', 'label' => 'Dutch'],
                ['value' => 'English', 'label' => 'English'],
                ['value' => 'Esperanto', 'label' => 'Esperanto'],
                ['value' => 'Estonian', 'label' => 'Estonian'],
                ['value' => 'Faroese', 'label' => 'Faroese'],
                ['value' => 'Filipino', 'label' => 'Filipino'],
                ['value' => 'Finnish', 'label' => 'Finnish'],
                ['value' => 'French', 'label' => 'French'],
                ['value' => 'Frisian', 'label' => 'Frisian'],
                ['value' => 'Galician', 'label' => 'Galician'],
                ['value' => 'Georgian', 'label' => 'Georgian'],
                ['value' => 'German', 'label' => 'German'],
                ['value' => 'Greek', 'label' => 'Greek'],
                ['value' => 'Gujarati', 'label' => 'Gujarati'],
                ['value' => 'Haitian Creole', 'label' => 'Haitian Creole'],
                ['value' => 'Hausa', 'label' => 'Hausa'],
                ['value' => 'Hawaiian', 'label' => 'Hawaiian'],
                ['value' => 'Hebrew', 'label' => 'Hebrew'],
                ['value' => 'Hindi', 'label' => 'Hindi'],
                ['value' => 'Hmong', 'label' => 'Hmong'],
                ['value' => 'Hungarian', 'label' => 'Hungarian'],
                ['value' => 'Icelandic', 'label' => 'Icelandic'],
                ['value' => 'Igbo', 'label' => 'Igbo'],
                ['value' => 'Indonesian', 'label' => 'Indonesian'],
                ['value' => 'Irish', 'label' => 'Irish'],
                ['value' => 'Italian', 'label' => 'Italian'],
                ['value' => 'Japanese', 'label' => 'Japanese'],
                ['value' => 'Javanese', 'label' => 'Javanese'],
                ['value' => 'Kannada', 'label' => 'Kannada'],
                ['value' => 'Kazakh', 'label' => 'Kazakh'],
                ['value' => 'Khmer', 'label' => 'Khmer'],
                ['value' => 'Kinyarwanda', 'label' => 'Kinyarwanda'],
                ['value' => 'Korean', 'label' => 'Korean'],
                ['value' => 'Kurdish', 'label' => 'Kurdish'],
                ['value' => 'Kyrgyz', 'label' => 'Kyrgyz'],
                ['value' => 'Lao', 'label' => 'Lao'],
                ['value' => 'Latin', 'label' => 'Latin'],
                ['value' => 'Latvian', 'label' => 'Latvian'],
                ['value' => 'Lithuanian', 'label' => 'Lithuanian'],
                ['value' => 'Luxembourgish', 'label' => 'Luxembourgish'],
                ['value' => 'Macedonian', 'label' => 'Macedonian'],
                ['value' => 'Malagasy', 'label' => 'Malagasy'],
                ['value' => 'Malay', 'label' => 'Malay'],
                ['value' => 'Malayalam', 'label' => 'Malayalam'],
                ['value' => 'Maltese', 'label' => 'Maltese'],
                ['value' => 'Maori', 'label' => 'Maori'],
                ['value' => 'Marathi', 'label' => 'Marathi'],
                ['value' => 'Mongolian', 'label' => 'Mongolian'],
                ['value' => 'Nepali', 'label' => 'Nepali'],
                ['value' => 'Norwegian', 'label' => 'Norwegian'],
                ['value' => 'Odia (Oriya)', 'label' => 'Odia (Oriya)'],
                ['value' => 'Pashto', 'label' => 'Pashto'],
                ['value' => 'Persian', 'label' => 'Persian'],
                ['value' => 'Polish', 'label' => 'Polish'],
                ['value' => 'Portuguese', 'label' => 'Portuguese'],
                ['value' => 'Punjabi', 'label' => 'Punjabi'],
                ['value' => 'Romanian', 'label' => 'Romanian'],
                ['value' => 'Russian', 'label' => 'Russian'],
                ['value' => 'Samoan', 'label' => 'Samoan'],
                ['value' => 'Scots Gaelic', 'label' => 'Scots Gaelic'],
                ['value' => 'Serbian', 'label' => 'Serbian'],
                ['value' => 'Sesotho', 'label' => 'Sesotho'],
                ['value' => 'Shona', 'label' => 'Shona'],
                ['value' => 'Sindhi', 'label' => 'Sindhi'],
                ['value' => 'Sinhala', 'label' => 'Sinhala'],
                ['value' => 'Slovak', 'label' => 'Slovak'],
                ['value' => 'Slovenian', 'label' => 'Slovenian'],
                ['value' => 'Somali', 'label' => 'Somali'],
                ['value' => 'Spanish', 'label' => 'Spanish'],
                ['value' => 'Sundanese', 'label' => 'Sundanese'],
                ['value' => 'Swahili', 'label' => 'Swahili'],
                ['value' => 'Swedish', 'label' => 'Swedish'],
                ['value' => 'Tajik', 'label' => 'Tajik'],
                ['value' => 'Tamil', 'label' => 'Tamil'],
                ['value' => 'Tatar', 'label' => 'Tatar'],
                ['value' => 'Telugu', 'label' => 'Telugu'],
                ['value' => 'Thai', 'label' => 'Thai'],
                ['value' => 'Tibetan', 'label' => 'Tibetan'],
                ['value' => 'Turkish', 'label' => 'Turkish'],
                ['value' => 'Turkmen', 'label' => 'Turkmen'],
                ['value' => 'Ukrainian', 'label' => 'Ukrainian'],
                ['value' => 'Urdu', 'label' => 'Urdu'],
                ['value' => 'Uyghur', 'label' => 'Uyghur'],
                ['value' => 'Uzbek', 'label' => 'Uzbek'],
                ['value' => 'Vietnamese', 'label' => 'Vietnamese'],
                ['value' => 'Welsh', 'label' => 'Welsh'],
                ['value' => 'Xhosa', 'label' => 'Xhosa'],
                ['value' => 'Yiddish', 'label' => 'Yiddish'],
                ['value' => 'Yoruba', 'label' => 'Yoruba'],
                ['value' => 'Zulu', 'label' => 'Zulu']
            ];

            $data = api_successWithData('languages', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
