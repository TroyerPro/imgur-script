<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidImage;
use App\Exceptions\InvalidParams;
use App\Image;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class ImagesController extends BaseController
{

	const IMAGES_AT_HOME = 32;
	const IMAGES_X_PAGE  = 16;
	const IMAGES_PATH    = 'uploads/';

	public function __construct()
	{
	}

	public function list()
	{
		return Image::orderBy( 'id', 'desc' )->take( self::IMAGES_AT_HOME )->get();
	}

	public function listPage( $page )
	{
		return Image::orderBy( 'id', 'desc' )->skip( $page * self::IMAGES_X_PAGE )->take( self::IMAGES_X_PAGE )->get();
	}

	public function infoByImageCode( $image_code )
	{
		$img = Image::where( 'image_code', '=', $image_code )->first();

		if ( is_null( $img ) )
			throw new InvalidImage( 'Code: ' . $image_code );

		return $img;

	}

	public function upload( Request $request )
	{
		$this->uploadCheckIfValid( $request );

		$image_ddbb_path = $this->uploadSaveInDisk( $request );
		$img             = $this->uploadSaveOnDDBB( $request, $image_ddbb_path );

		return $img;

	}

	private function uploadCheckIfValid( Request $request )
	{

		if ( $request->hasFile( 'imagen' ) === false || $request->file( 'imagen' )->isValid() === false )
			throw new InvalidParams( $request->imagen->getErrorMessage() );

		$mimetype         = $request->imagen->getMimeType();
		$client_extension = $request->imagen->getClientOriginalExtension();

		switch ( $mimetype ) {
			case 'image/jpeg':
			case 'image/png':
				if ( $client_extension !== 'jpg' && $client_extension !== 'png' )
					throw new InvalidImage( $mimetype . ": " . $client_extension );
				break;
			case 'image/gif':
				if ( $client_extension !== 'gif' )
					throw new InvalidImage( $mimetype . ": " . $client_extension );
				break;
			default:
				throw new InvalidImage( $mimetype . ": " . $client_extension );
				break;
		}

	}

	private function uploadSaveInDisk( Request $request )
	{

		$client_extension = $request->imagen->getClientOriginalExtension();

		$image_name          = time() . rand( 100000, 999999 ) . '.' . $client_extension;
		$image_physical_path = self::IMAGES_PATH . $image_name;
		$image_ddbb_path     = "/" . $image_physical_path;

		move_uploaded_file( $_FILES[ 'imagen' ][ 'tmp_name' ], $image_physical_path );

		return $image_ddbb_path;

	}

	private function uploadSaveOnDDBB( Request $request, $image_ddbb_path )
	{

		$img             = new Image();
		$img->user_id    = NULL;
		$img->name       = '';
		$img->path       = $image_ddbb_path;
		$img->image_code = rand( 100000, 999999 ) . time();
		$img->ip         = $request->ip();
		$img->save();

		return $img;
	}
}
