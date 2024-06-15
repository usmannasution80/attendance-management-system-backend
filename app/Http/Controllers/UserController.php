<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Custom\Constants;
use Illuminate\Support\Facades\DB;
use Codedge\Fpdf\Fpdf\Fpdf;
use chillerlan\QRCode\{QRCode, QROptions};

class UserController extends Controller {

  protected $validations;

  public function __construct(){

    $this->validations= [
      'id' => 'integer|exists:users',
      'name' => 'required|string',
      'grade' => 'required_with_all:department,class|integer|min:10',
      'department' => 'required_with_all:grade,class|in:'.implode(',', array_keys(Constants::DEPARTMENTS)),
      'class' => 'required_with_all:grade,department|integer|min:1',
      'email' => 'email|required_if:is_admin,true',
    ];

  }

  public function index(Request $req){
    return User::whereRaw(
      strtotime($req->input('updated_at')) ?
      "updated_at > '{$req->input('last_update')}'" :
      1
    )
    ->get()
    ->toArray();
  }

  public function get(string $id){
    return User::find($id);
  }

  public function create(Request $req){

    $req->validate($this->validations);

    $user = new User();
    $user->name = $req->name;
    $user->grade = $req->grade;
    $user->department = $req->department;
    $user->{'class'} = $req->input('class');

    if($req->is_admin)
      $user->password = Hash::make($user->email);

    $user->save();

    return $user->toArray();

  }

  public function update(Request $req){

    $req->validate($this->validations);

    $user = User::find($req->input('id'));

    if(!$user)
      abort(404);

    $user->name = $req->name;
    $user->grade = $req->grade;
    $user->department = $req->department;
    $user->{'class'} = $req->input('class');

    if($req->is_admin)
      $user->password = Hash::make($user->email);
    else
      $user->password = null;

    $user->save();

    return $user->toArray();

  }

  public function delete($id){

    User::destroy($id);

    DB::table('attendance_lists')
    ->update([
      'data' => DB::raw(
        "REGEXP_REPLACE(
          data,
          '{$id},[0-9]+,[^;]+;?',
          ''
        )"
      )
    ]);

    return response()->noContent();

  }

  public function generate_card($user, $force = false){

    if(!$user)
      abort(404);

    $qr_options = new QROptions();
    $qr_options->imageBase64 = false;
    $qr_options->outputType = 'png';
    $qr_options->jpegQuality = 1;
    $qr_options->cachefile = public_path()."/temp/qr_{$user['id']}.png";

    if(!$force)
      if(file_exists($qr_options->cachefile))
        return $qr_options->cachefile;

    $img = imagecreatefrompng(public_path() . '/qr_bg.png');
    $qr = new QRCode();

    (new QRCode($qr_options))->render($user->id);
    $qr_wh = intval(imagesx($img) * 82 / 100);
    $qr = imagecreatefrompng($qr_options->cachefile);

    imagecopyresized(
      $img,
      $qr,
      intval(imagesx($img) / 2 - $qr_wh / 2),
      550,
      0,
      0,
      $qr_wh,
      $qr_wh,
      imagesx($qr),
      imagesy($qr)
    );

    $width = imagesx($img);
    $center_x = $width / 2;
    $font_size = 100;
    $font = public_path() . '/Ubuntu-Bold.ttf';
    $text = $user->name;
    do{
      $text = preg_replace('/\s\w+$/i', '', $text);
      list($left, , $right, , ,) = imageftbbox($font_size, 0, $font, $text);
    }while($right > $width);
    $left_offset = ($right - $left) / 2;
    $x = $center_x - $left_offset;
    $imagettfstroketext = function(&$image, $size, $angle, $x, $y, $textcolor, $strokecolor, $fontfile, $text, $px) {
      for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
      return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
    };

    $imagettfstroketext(
      $img,
      $font_size,
      0,
      floor($center_x - $left_offset),
      2300,
      imagecolorallocate($img, 3, 169, 244),
      imagecolorallocate($img, 255, 255, 255),
      $font,
      $text,
      5
    );

    $text = $user->grade . ' ' . strtoupper($user->department) . '-' . $user->class;
    list($left, , $right, , ,) = imageftbbox($font_size, 0, $font, $text);
    $left_offset = ($right - $left) / 2;

    $imagettfstroketext(
      $img,
      $font_size,
      0,
      floor($center_x - $left_offset),
      2500,
      imagecolorallocate($img, 0, 0, 0),
      imagecolorallocate($img, 255, 255, 255),
      $font,
      $text,
      5
    );

    imagepng($img, $qr_options->cachefile);
    return $qr_options->cachefile;

  }

  public function get_card($id){
    $user = User::find($id);
    header('Content-Disposition: attachment; filename=qr_card_' . $user->id . '.png');
    header('Content-Type: image/png');
    return readfile($this->generate_card($user));
  }

  public function download_cards(){
    ini_set('max_execution_time', 0);
    $pdf = new Fpdf();
    $pdf->AddPage();
    $users = new User();
    if(isset($_GET['grade']))
      $users = $users->where('grade', $_GET['grade']);
    if(isset($_GET['department']))
      $users = $users->where('department', $_GET['department']);
    if(isset($_GET['class']))
      $users = $users->where('class', $_GET['class']);
    if(isset($_GET['name']))
      $users = $users->where('name', 'like', "%${_GET['name']}%");
    $users = $users->get();
    $column = 1;
    $row    = 0;
    $spacing = 10;
    foreach($users as $user){
      $qr = $this->generate_card($user);
      list($img_width, $img_height) = getimagesize($qr);

      $width = $pdf->getPageWidth() / 2 - $spacing * 1.5;
      $height = $img_height * $width / $img_width;
      if($row * $height + $spacing * ($row + 1) + $height > $pdf->getPageHeight()){
        $pdf->AddPage();
        $row = 0;
      }

      $x = $column === 1 ? $spacing : $width + 2 * $spacing;
      $y = $row * $height + $spacing * ($row + 1);

      $pdf->setXY($x, $y);
      $pdf->Image(
        $qr,
        $x,
        $y,
        $width,
        $height
      );
      if($column === -1)
        $row++;
      $column *= -1;
    }
    $doc = public_path() . '/temp/doc.pdf';
    $pdf->Output('F', $doc);
    return response()->download($doc);
    ini_set('max_execution_time', 30);
  }

}
