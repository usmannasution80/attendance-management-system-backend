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

  public function get_card($id){
    $user = User::find($id);
    if(!$user)
      abort(404);
    $img = imagecreatefrompng(public_path() . '/qr_bg.png');
    $qr_options = new QROptions();
    $qr_options->imageBase64 = false;
    $qr_options->outputType = 'png';
    $qr_options->jpegQuality = 1;
    $qr = new QRCode();
    $qr_options->cachefile = public_path()."/temp/qr_{$user['id']}.png";
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
    header('Content-Disposition: attachment; filename=qr_card_' . $id . '.png');
    header('Content-Type: image/png');

    imagepng($img);
  }

  public function download_cards(){
    ini_set('max_execution_time', 2*60);
    $height = 40;
    $header_height = 15;
    $pdf = new Fpdf();
    $pdf->AddPage();
    $users = User::all();
    $column = 1;
    $row    = 0;
    $spacing = 10;
    $width = $pdf->getPageWidth() / 2 - $spacing * 1.5;
    $qr_options = new QROptions();
    $qr_options->imageBase64 = false;
    $qr_options->outputType = 'png';
    $qr_options->jpegQuality = 1;
    $qr = new QRCode();
    $i = 0;
    foreach($users as $user){
      $i++;

      if($row * $height + $spacing * ($row + 1) + $height > $pdf->getPageHeight()){
        $pdf->AddPage();
        $row = 0;
      }

      $x = $column === 1 ? $spacing : $width + 2 * $spacing;
      $y = $row * $height + $spacing * ($row + 1);

      $pdf->setXY($x, $y);

      $pdf->Cell(
        $width,
        $height,
        '',
        1
      );

      $pdf->setXY($x, $y);
      $pdf->Cell(
        $width,
        $header_height,
        '',
        'B'
      );
      $pdf->setXY($x, $y + 2);
      $pdf->SetFont('Courier', 'B', 12);
      $pdf->Cell(
        $width,
        $header_height / 3,
        'KARTU ABSENSI',
        0,
        0,
        'C'
      );
      $pdf->setXY($x, $y + $header_height / 3);
      $pdf->SetFont('Courier', 'B', 16);
      $pdf->Cell(
        $width,
        $header_height / 3 * 2,
        'SMKN 2 PANYABUNGAN',
        0,
        0,
        'C'
      );
      $pdf->setXY($x, $y + $header_height);
      $qr_options->cachefile = public_path()."/qr_temp_{$user['id']}.png";
      (new QRCode($qr_options))->render($user['id']);
      $pdf->Image(
        $qr_options->cachefile,
        null,
        null,
        $width / 3 - 5
      );
      unlink($qr_options->cachefile);
      $content = "NAMA    : {$user['name']}\n"
               . "KELAS   : {$user['grade']}\n"
               . "JURUSAN : {$user['department']}\n"
               . "RUANG   : {$user['class']}";
      $pdf->setXY($x + $width / 3, $y + $header_height + 2);
      $pdf->SetFont('Courier', '', 10);
      $pdf->MultiCell(
        $width / 3 * 2,
        5,
        $content
      );

      if($column === -1)
        $row++;
      $column *= -1;

    }
    $pdf->Output('F', public_path().'/cards.pdf');
    return ['url' => url('cards.pdf')];
  }

}
