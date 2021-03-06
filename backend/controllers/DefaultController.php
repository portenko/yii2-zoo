<?php
/**
 * @link https://github.com/worstinme/yii2-user
 * @copyright Copyright (c) 2014 Evgeny Zakirov
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace worstinme\zoo\backend\controllers;

use worstinme\zoo\helpers\Inflector;
use Yii;
use worstinme\zoo\backend\models\Applications;
use worstinme\zoo\models\Items;

use yii\web\NotFoundHttpException;

class DefaultController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'only' => ['index', 'create', 'update','elfinder','delete'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['update', 'create','elfinder'],
                        'roles' => $this->module->accessRoles !== null ? $this->module->accessRoles : ['admin'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => $this->module->accessRoles !== null ? $this->module->accessRoles : ['admin','moder'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create','delete'],
                        'roles' => $this->module->accessRoles !== null ? $this->module->accessRoles : ['superadmin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete' => ['post','delete'],
                ],
            ],
        ];
    }

    public function actionAliasCreate()
    {
        $alias = Inflector::slug(Yii::$app->request->post('name'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if  (Yii::$app->request->post('nodelimiter')) {
            $alias = str_replace("-","_",$alias);
        }

        return [
            'alias' => $alias,
            'code' => 100,
        ];
    }

    public function actionIndex()
    {
    	$applications = Yii::$app->zoo->applications;

        $model = new Applications;

        if (Yii::$app->zoo->frontendPath === null || !is_dir(Yii::getAlias(Yii::$app->zoo->frontendPath))) {
            $model->app_alias = '@app';
        }
        else {
            $model->app_alias = Yii::$app->zoo->frontendPath;
        }

        return $this->render('index',[
            'applications'=>$applications,
            'model'=>$model,
        ]); 
    }

    // создание приложения

    public function actionCreate()
    {
        
        $model = new Applications;

        if (Yii::$app->zoo->frontendPath === null || !is_dir(Yii::getAlias(Yii::$app->zoo->frontendPath))) {
            $model->app_alias = '@app';
        }
        else {
            $model->app_alias = Yii::$app->zoo->frontendPath;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $controller = strtolower($model->name);

            $urlRules = "<code>".\yii\helpers\Html::encode("'$controller/search'=>'$controller/default/search',\n
                '$controller/<b:[\w\-]+>/<c:[\w\-]+>/<d:[\w\-]+>/<e:[\w\-]+>'=>'$controller/default/abcde',\n
                '$controller/<b:[\w\-]+>/<c:[\w\-]+>/<d:[\w\-]+>'=>'$controller/default/abcd',\n
                '$controller/<b:[\w\-]+>/<c:[\w\-]+>'=>'$controller/default/abc',\n
                '$controller/<b:[\w\-]+>'=>'$controller/default/ab',\n
                '$controller'=>'$controller/default/index',")."</code>";
            
            Yii::$app->getSession()->setFlash('success', Yii::t('zoo','Приложение добавлено. Добавьте в web.php правила для обработки ссылок'.$urlRules));

            return $this->redirect(['index']);
            
        } 

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    // настройки приложения

    public function actionUpdate()
    {

        $model = $this->getApp();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->getSession()->setFlash('success', Yii::t('zoo','Настройки сохранены'));
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }


    public function actionDelete()
    {

        $model = $this->getApp();

        if ($model !== null && $model->delete()) {
            Yii::$app->getSession()->setFlash('warning', Yii::t('zoo','Приложение удалено.'));
        }
        else {
            Yii::$app->getSession()->setFlash('warning', Yii::t('zoo','Что-то пошло не так.'));
        }

        return $this->redirect(['index']);

    }

}