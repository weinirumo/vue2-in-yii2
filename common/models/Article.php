<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "article".
 *
 * @property integer $id
 * @property string $title
 * @property string $text
 * @property string $html
 * @property string $author_id
 * @property string $author_name
 * @property string $type
 * @property string $tag
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $url
 * @property string $comment_count
 */
class Article extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'article';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['text', 'html'], 'string'],
            [['status','author_id'], 'integer'],
            [['created_at','updated_at'], 'safe'],
            [['title', 'author_name', 'type', 'tag', 'url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'text' => 'Text',
            'html' => 'Html',
            'author_id' => 'Author ID',
            'author_name' => 'Author Name',
            'type' => 'Type',
            'tag' => 'Tag',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'url' => 'Url',
            'comment_count' => 'Comment Count',
        ];
    }

    /**
     * @param $title
     * @param $text
     * @param $html
     * @param $tags
     * @return bool
     */
    public function publish($title,$text,$html,$tags){
        $User=Yii::$app->user->getIdentity();

        $this->title=$title;
        $this->text=$text;
        $this->html=$html;
        $this->tag='';
        $this->url='';
        $this->type='';
        $this->author_id=$User->id;
        $this->author_name=$User->username;
        $this->status=0;
        if($this->save()){
            $this->addTags($tags);
            return 0;
        }else{
            return json_encode($this->errors);
        }
    }

    public function edit($title,$text,$html,$tags)
    {
        $this->title=$title;
        $this->text=$text;
        $this->html=$html;
        if($this->save()){
            //TODO update tags
            return 0;
        }else{
            return json_encode($this->errors);
        }
    }

    public function addTags($tags){
        if(is_array($tags)){
            foreach ($tags as $tag){
                if($tag->isNew && Tag::find()->where(['name'=>$tag->name])->count()==0){
                    $tagModel=new Tag(['name'=>$tag->name]);
                    $tagId=$tagModel->add();
                }else{
                    $tagId=Tag::findOne(['name'=>$tag->name])->id;
                }
                $rs=new Relationship(['cid'=>$tagId,'pid'=>$this->id,'type'=>'tag-article']);
                $rs->save();
                $tagArticleCount=Tag::findOne(['id'=>$tagId])->refreshCount();
            }
            return 0;
        }
    }

    /**
     * update comment_count column
     * @return int|string
     */
    public function countComments(){
        $count=Relationship::find()->where(['type'=>'comment-article','pid'=>$this->id])->count();
        $this->comment_count=$count;
        $r=$this->save();
        return $count;
    }

    public static function batchAdd($articles,$autoId){
        if($autoId)$cols=['title','text','html','author_id','author_name','type','tag','status','created_at','updated_at','url','comment_count'];
        else $cols=['id','title','text','html','author_id','author_name','type','tag','status','created_at','updated_at','url','comment_count'];
        $result=Yii::$app->db->createCommand()->batchInsert(static::tableName(),$cols,$articles)->execute();
        return $result;
    }

    public static function getByTagUrl($url){
        $tag=Tag::findOne(['url'=>$url]);
        if(isset($tag)){
            $tagId=$tag->id;
        }else{
            return false;
        }
        $relationshipQuery=(new Query())->select('pid')->from('relationship')->where(['cid'=>$tagId,'type'=>'tag-article']);
        return static::find()->where(['id'=>$relationshipQuery])->all();
    }

    public static function getByUrl($url){
        return static::findOne(['url'=>$url]);
    }

    public function getAllList(){
        return self::find()->select(['id','title','author_name','created_at'])->orderBy('created_at DESC')->asArray()->all();
    }

    public static function exportAllData(){
        return self::find()->asArray()->all();
    }

    public static function getAllHead(){
        return self::find()->select(['id','title','text','author_name','created_at'])->orderBy('created_at DESC')->asArray()->all();
    }

    public static function getText($id){
        return static::findOne($id)->text;
    }
}
