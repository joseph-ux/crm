<?php


namespace api\crm\controller;


use api\crm\model\WechatUserAddressModel;
use api\crm\model\WechatUserModel;
use app\admin\logic\HookLogic;
use cmf\controller\RestBaseController;

class WechatUserController extends RestBaseController
{

    public function add_user_address()
    {
        $user_id = $this->getWechatUserId();
        $data = $this->request->param();
        $validate_result = $this->validate($data, 'UserAddress');
        if ($validate_result !== true) {
            $this->error($validate_result);
        } else {
            $data['user_id'] = $user_id;
            $is_default = isset($data['is_default']) && $data['is_default'] ? $data['is_default'] : 0;
            if ($is_default == 1) {
                $list = WechatUserAddressModel::where('user_id', $user_id)->all();
                if ($list) {
                    WechatUserAddressModel::where('user_id', $user_id)->update(['is_default' => 0]);
                }
            }
            $model = WechatUserAddressModel::create($data);
            $res = $model->save();
            if ($res) {
                $this->success('新增成功');
            } else {
                $this->error('新增失败');
            }
        }
    }

    public function edit_user_address()
    {
        $data = $this->request->param();
        $user_id = $this->getWechatUserId();
        $validate_result = $this->validate($data, 'UserAddress');
        if ($validate_result !== true) {
            $this->error($validate_result);
        }
        $id = isset($data['id']) && $data['id'] ? intval($data['id']) : 0;
        $address_info = WechatUserAddressModel::where(['user_id' => $user_id, 'id' => $id])->find();
        if (empty($address_info)) {
            $this->error('地址不存在或已被删除');
        } else {
            $is_default = isset($data['is_default']) && $data['is_default'] ? $data['is_default'] : 0;
            if ($is_default == 1) {
                $list = WechatUserAddressModel::where('user_id', $user_id)->all();
                if ($list) {
                    WechatUserAddressModel::where('user_id', $user_id)->update(['is_default' => 0]);
                }
            }
            $address_info->is_default = $is_default;
            $address_info->province = $data['province'];
            $address_info->city = $data['city'];
            $address_info->area = $data['area'];
            $address_info->address = $data['address'];
            $address_info->mobile = $data['mobile'];
            $address_info->name = $data['name'];
            $res = $address_info->save();
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function get_user_address_detail()
    {
        $data = $this->request->param();
        $user_id = $this->getWechatUserId();
        $id = isset($data['id']) && $data['id'] ? intval($data['id']) : 0;
        $address_info = WechatUserAddressModel::where(['user_id' => $user_id, 'id' => $id])->find();
        if (empty($address_info)) {
            $this->error('地址不存在或已被删除');
        } else {
            $this->success('获取地址详细信息', $address_info);
        }
    }

    public function set_user_default_address()
    {
        $user_id = $this->getWechatUserId();
        $data = $this->request->param();
        $id = isset($data['id']) && $data['id'] ? $data['id'] : 1;
        $model = WechatUserAddressModel::where(['id' => $id, 'user_id' => $user_id])->find();
        if ($model) {
            $list = WechatUserAddressModel::where('user_id', $user_id)->all();
            if ($list) {
                WechatUserAddressModel::where('user_id', $user_id)->update(['is_default' => 0]);
            }
            $model->is_default = 1;
            $res = $model->save();
            if ($res) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            $this->error('数据异常');
        }
    }

    public function delete_user_address()
    {
        $user_id = $this->getWechatUserId();
        $data = $this->request->param();
        $id = isset($data['id']) && $data['id'] ? $data['id'] : 0;
        $info = WechatUserAddressModel::where('user_id', $user_id)->get($id);
        if ($info) {
            $res = $info->delete();
            if ($res) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('地址不存在或已被删除');
        }
    }

    public function get_wechat_user_info()
    {
        $user_id = $this->getWechatUserId();
        $user_info = WechatUserModel::get($user_id);
        $this->success('获取微信用户信息', $user_info);
    }

    public function get_user_address()
    {
        $user_id = $this->getWechatUserId();
        $address_list = WechatUserAddressModel::where('user_id', $user_id)
            ->order('is_default desc,create_time desc')->all();
        $this->success('获取用户地址列表', $address_list);
    }

    public function pay_order()
    {
        $params['company_id'] = 1;
        $res = hook('wechat_web_pay_order', $params);
        $this->success('', $res);
    }

    public function refund_order()
    {
        $data = $this->request->param();

        $param['order_sn'] = $data['order_sn'];
        $param['total_money'] = $data['money'];
        $param['refund_money'] = $data['money'];
        $param['company_id'] = $data['id'];
        $param['desc'] = '活动退款';
        $res = hook('wechat_web_refund', $param);
        $this->success('退款成功', $res);
    }
}
