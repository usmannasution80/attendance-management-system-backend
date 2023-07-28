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

  public function download_cards(){
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
    $qr_options->jpegQuality = 10;
    $qr = new QRCode();
    foreach($users as $user){

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
