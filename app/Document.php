<?php

namespace App;
use App\D;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Document extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'id', 'title', 'category_id','sub_category_id', 'updation_date', 'is_active'
    ];
    protected $table = 'document_lists';
    public $timestamps = false;

    /**
     * @return array
     */
    public function getDocumentLists($categoryFilter,$subCategoryFilter,$categoriesId = false,$subCategoriesId=false)
    {
        $category = \App\Document::query();

        $category->join('pdf_reports', 'pdf_reports.document_id', '=', 'document_lists.id');
        $category->join('categorys', 'categorys.id', '=', 'document_lists.category_id');
        $category->join('sub_categorys', 'sub_categorys.id', '=', 'document_lists.sub_category_id');

        if($categoryFilter){

            $category->where('categorys.category_name','=', $categoryFilter);

            if($subCategoryFilter){
                $category->where('sub_categorys.sub_category_name','=', $subCategoryFilter);
            }
        }

        if($categoriesId){

            $category->where('document_lists.category_id','=', $categoriesId);

            if($subCategoriesId){
                $category->where('document_lists.sub_category_id','=', $subCategoriesId);
            }
        }

        $category->where('document_lists.is_active','=', 1);
        $category->where('pdf_reports.is_active','=', 1);
        $category->orderby('categorys.category_name');
        $data = $category->orderby('sub_categorys.sub_category_name')->get();
        return $data;
    }

    public function UploadDocument($categoryFilter,$subCategoryFilter,$pdfpath,$title){

        $category = \App\Document::query();
        $category->select( array('document_lists.id'));
        $category->leftjoin('categorys', 'categorys.id', '=', 'document_lists.category_id');
        $category->leftjoin('sub_categorys', 'sub_categorys.id', '=', 'document_lists.sub_category_id');
        $category->where('document_lists.is_active','=', 1);
        $category->where('categorys.is_active','=', 1);
        $category->where('sub_categorys.is_active','=', 1);
        $category->where('sub_categorys.sub_category_name','=', $subCategoryFilter);
        $existingCategory = $category->where('categorys.category_name','=', $categoryFilter)->get();

        $documentId = $existingCategory->toArray();


        if($documentId){
            $document = $this->whereRaw('id = '.$documentId[0]['id'] );
            $document->update(['title' =>$title,'updation_date'=>date("Y-m-d H:i:s")]);

            $pdfTable = new PdfReport();

            $pdf = $pdfTable->whereRaw('document_id = '.$documentId[0]['id'] );
            $pdf->whereRaw('is_active = 1');
            $pdf->update(['is_active' =>0]);

            $pdfTable->pdf_name = $pdfpath['pdf0'];
            $pdfTable->uploading_date = date('Y-m-d H:i:s');
            $pdfTable->document_id = $documentId[0]['id'];
            $pdfTable->save();

            return 'Success';
        }else{

            $categoryIds = \App\Category::query();
            $categoryIds->select( array('categorys.id as categoryId','sub_categorys.id as subCategoryId'));
            $categoryIds->leftJoin('sub_categorys','sub_categorys.category_id','=','categorys.id' );
            $categoryIds->where('categorys.category_name','=', $categoryFilter);
            $categoryIds = $categoryIds->where('sub_categorys.sub_category_name','=', $subCategoryFilter)->get();
            $id = $categoryIds->toArray();

            $documentObj = new Document();
            $documentObj->title = $title;
            $documentObj->category_id = $id[0]['categoryId'];
            $documentObj->sub_category_id = $id[0]['subCategoryId'];
            $documentObj->updation_date = date('Y-m-d H:i:s');
            $documentObj->save();

            $pdfTable = new PdfReport();
            $pdfTable->pdf_name = $pdfpath['pdf0'];
            $pdfTable->uploading_date = date('Y-m-d H:i:s');
            $pdfTable->document_id = $documentObj->id;
            $pdfTable->save();
            return 'Success';
        }


    }

    public function deleteDocument($docId){

        $document = $this->whereRaw('id = '.$docId['documentId']);
        $document->update(['is_active' => 0]);

        $pdfTable = new PdfReport();
        $pdfRecord = $pdfTable->whereRaw('document_id = '.$docId['documentId']);
        $pdfRecord->update(['is_active' => 0]);
        return 'success';

    }

}
