<?php


namespace app\admin\controller;


use app\admin\model\MaterialModel;
use app\admin\model\MemberCardInfoModel;
use app\admin\model\RoleUserModel;
use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;
use Overtrue\Socialite\User;

class MemberCardController extends AdminBaseController
{
    public function index()
    {
        $admin_id = cmf_get_current_admin_id();
        $role_id = RoleUserModel::where('user_id', $admin_id)->value('role_id');
        $role_id = $role_id ? $role_id : 1;
        $this->assign('role_id', $role_id);
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 10;
            $page = isset($data['page']) && $data['page'] ? $data['page'] : 1;
            $admin_id = cmf_get_current_admin_id();
            $admin_info = UserModel::get($admin_id);
            $where = [];
            if ($admin_info['company_id']) {
                $where[] = ['company_id', '=', $admin_info['company_id']];
            }
            $list = MemberCardInfoModel::with('company_info')->where($where)
                ->paginate($num, false, ['page' => $page])->each(function ($item) {
                    if ($item['begin_timestamp'] && $item['end_timestamp']) {
                        $begin_timestamp = date('Y-m-d', $item['begin_timestamp']);
                        $end_timestamp = date('Y-m-d', $item['end_timestamp']);
                        $item['activity_time'] = [$begin_timestamp, $end_timestamp];
                    }
                });
            $this->success('', '', $list);
        }
        return $this->fetch();
    }

    public function update()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $activity_time = isset($data['activity_time']) && $data['activity_time'] ? $data['activity_time'] : '';

            if (isset($data['id']) && $data['id']) {
                $model = MemberCardInfoModel::get($data['id']);
            } else {
                $model = new MemberCardInfoModel();
            }
            $admin_id = cmf_get_current_admin_id();
            $admin_info = UserModel::get($admin_id);
            if ($admin_info['company_id']) {
                $model->company_id = $admin_info['company_id'];
            } else {
                $model->company_id = isset($data['company_id']) && $data['company_id'] ? $data['company_id'] : 0;
            }
            if ($activity_time) {
                $model->begin_timestamp = strtotime($data['activity_time'][0]);
                $model->end_timestamp = strtotime($data['activity_time'][1]);
            }
            $model->title = $data['title'];
            $model->color = $data['color'];
            $model->logo_url = $data['logo_url'];
            $model->code_type = $data['code_type'];
            $model->background_pic_url = $data['background_pic_url'];
            $model->brand_name = $data['brand_name'];
            $model->prerogative = $data['prerogative'];
            $model->notice = $data['notice'];
            $model->description = $data['description'];
            $model->quantity = $data['quantity'];
            $model->date_info_type = $data['date_info_type'];
            $model->fixed_term = $data['fixed_term'];
            $model->fixed_begin_term = $data['fixed_begin_term'];
            $model->service_phone = $data['service_phone'];
            $model->auto_activate = $data['auto_activate'];
            $model->supply_bonus = $data['supply_bonus'];
            $model->supply_balance = $data['supply_balance'];
            $model->bonus_url = $data['bonus_url'];
            $model->balance_url = $data['balance_url'];
            $model->wx_activate = $data['wx_activate'];
            $res = $model->save();
            if ($res) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    public function sync_member_card()
    {
        $data = $this->request->param();
        $id = isset($data['id']) && $data['id'] ? $data['id'] : 0;
        $card_info = MemberCardInfoModel::get($id);
        if (empty($card_info)) {
            $this->error('会员卡信息不存在或已被删除');
        }
        if ($card_info['card_id']) {
            //修改会员卡信息
        } else {
            //创建会员卡信息
            $logo_material = MaterialModel::where([
                'local_url' => $card_info['logo_url'],
                'company_id' => $card_info['company_id']])
                ->find();
            if (empty($logo_material)) {
                $logo_data['type'] = 'img';
                $logo_data['company_id'] = $card_info['company_id'];
                $logo_data['path'] = CMF_ROOT . 'public' . $card_info['logo_url'];
                $result1 = hook('upload_material', $logo_data);
                $logo_material = new MaterialModel([
                    'company_id' => $card_info['company_id'],
                    'local_url' => $card_info['logo_url'],
                    'media_id' => $result1[0]['media_id'],
                    'wechat_url' => $result1[0]['url']

                ]);
                $logo_material->save();
                $card_info['logo_url'] = $result1[0]['url'];
            } else {
                $card_info['logo_url'] = $logo_material['wechat_url'];
            }

            $background_pic_url_material = MaterialModel::where([
                'local_url' => $card_info['background_pic_url'],
                'company_id' => $card_info['company_id']])
                ->find();
            if (empty($background_pic_url_material)) {
                $background_pic_data['type'] = 'img';
                $background_pic_data['company_id'] = $card_info['company_id'];
                $background_pic_data['path'] = CMF_ROOT . 'public' . $card_info['background_pic_url'];
                $result1 = hook('upload_material', $background_pic_data);
                $logo_material = new MaterialModel([
                    'company_id' => $card_info['company_id'],
                    'local_url' => $card_info['background_pic_url'],
                    'media_id' => $result1[0]['media_id'],
                    'wechat_url' => $result1[0]['url']

                ]);
                $logo_material->save();
                $card_info['background_pic_url'] = $result1[0]['url'];
            } else {
                $card_info['background_pic_url'] = $background_pic_url_material['wechat_url'];
            }

            $res = hook('create_member_card', $card_info);
            $this->success('', '', $res);
        }
    }
}
