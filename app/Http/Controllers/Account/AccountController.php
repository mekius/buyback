<?php

namespace App\Http\Controllers\Account;

use App\Models\EveApi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Station;
use App\Models\InvType;

class AccountController extends Controller
{
    private $_structureCache = [];

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        $api = EveApi::getXmlApiInstance();

        $response = $api->AssetList(["characterID" => $user->provider_user_id, 'flat' => 1]);
        $assets = $response->assets->toArray();

        $ores = $this->_getOreCollection();
        $oreTypes = array();
        foreach ($ores as $ore) {
            $oreTypes[] = $ore->typeID;
        }

        $locations = config('locations');
        $locationIds = array_keys($locations);

        $assetList = [];
        foreach ($assets as $asset) {

            if (!in_array($asset['typeID'], $oreTypes)) {
                continue;
            }

            if (!in_array($asset['locationID'], $locationIds)) {
                continue;
            }

            $station = Station::find($asset['locationID']);
            if (!$station) {
                if (!isset($this->_structureCache[$asset['locationID']])) {
                    // It may be a structure ID, try looking it up that way
                    $api_instance = new \ESI\Api\UniverseApi();
                    $result = $api_instance->getUniverseStructuresStructureId($asset['locationID']);
                    if ($result instanceof \ESI\Model\GetUniverseStructuresStructureIdOk) {
                        $this->_structureCache[$asset['locationID']] = $result;
                    }
                }

                $asset['locationName'] = $this->_structureCache[$asset['locationID']]->getName();
            } else {
                $asset['locationName'] = $station->stationName;
            }

            $type = InvType::find($asset['typeID']);
            if ($type) {
                $asset['typeName'] = $type->typeName;
            }

            $assetList[$asset['locationID']][$asset['typeID']] = $asset;
        }

        return view('account.index', ['user' => $user, 'assetList' => $assetList]);
    }

    private function _getOreCollection()
    {
        return InvType::with('marketItem')->whereIn('typeName', config('types.groups.ore'))->where('marketGroupID', '!=', 'NULL')->orderBy('volume', 'ASC')->orderBy('marketGroupID', 'ASC')->orderBy('groupId', 'ASC')->orderBy('typeName', 'ASC')->get();
    }
}
