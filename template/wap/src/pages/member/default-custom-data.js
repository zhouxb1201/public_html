const defaultData = {
  items: {
    "M0_member": {
      "id": "member_fixed",
      "style": {
        "backgroundimage": ""
      },
      "params": {
        "styletype": '1'
      }
    },
    "M0_member_bind": {
      "id": "member_bind_fixed",
      "style": {
        "background": "#fff",
        "iconcolor": '#ff454e',
        "titlecolor": '#323233',
        "desccolor": '#909399',
      },
      "params": {
        "title": '绑定手机',
        "desc": '为了账号安全、方便购物和订单同步，请绑定手机号码。'
      }
    },
    "M0_member_assets": {
      "id": "member_assets_fixed",
      "style": {
        "background": "#fff",
        "textcolor": '#323233',
        "iconcolor": '#323233',
        "highlight": '#ff454e',
        "titlecolor": '#323233',
        "titleiconcolor": '#323233',
        "titleremarkcolor": '#909399',
      },
      "params": {
        "title": '我的资产',
        "remark": '更多',
        "iconclass": 'v-icon-assets'
      },
      "data": {
        "C0_balance": {
          "key": 'balance',
          "name": '余额',
          "text": '余额',
          "is_show": '1',
        },
        "C0_points": {
          "key": 'points',
          "name": '积分',
          "text": '积分',
          "is_show": '1',
        },
        "C0_coupontype": {
          "key": 'coupontype',
          "name": '优惠券',
          "text": '优惠券',
          "is_show": '1',
        },
        "C0_giftvoucher": {
          "key": 'giftvoucher',
          "name": '礼品券',
          "text": '礼品券',
          "is_show": '1',
        },
        "C0_store": {
          "key": 'store',
          "name": '消费卡',
          "text": '消费卡',
          "is_show": '1',
        },
        "C0_blockchain": {
          "key": 'blockchain',
          "name": '数字钱包',
          "text": '数字钱包',
          "is_show": '1',
        }
      }
    },
    "M0_member_order": {
      "id": "member_order_fixed",
      "style": {
        "background": "#fff",
        "textcolor": '#323233',
        "iconcolor": '#323233',
        "titlecolor": '#323233',
        "titleiconcolor": '#323233',
        "titleremarkcolor": '#909399',
      },
      "params": {
        "title": '我的订单',
        "remark": '全部订单',
        "iconclass": 'v-icon-form'
      },
      "data": {
        "C0123456789101": {
          "key": 'unpaid',
          "name": '待付款',
          "text": '待付款',
          "iconclass": 'v-icon-payment2',
          "is_show": '1',
        },
        "C0123456789102": {
          "key": 'unshipped',
          "name": '待发货',
          "text": '待发货',
          "iconclass": 'v-icon-delivery2',
          "is_show": '1',
        },
        "C0123456789103": {
          "key": 'unreceived',
          "name": '待收货',
          "text": '待收货',
          "iconclass": 'v-icon-logistic3',
          "is_show": '1',
        },
        "C0123456789104": {
          "key": 'unevaluated',
          "name": '待评价',
          "text": '待评价',
          "iconclass": 'v-icon-success1',
          "is_show": '1',
        },
        "C0123456789105": {
          "key": 'aftersale',
          "name": '售后',
          "text": '售后',
          "iconclass": 'v-icon-sale',
          "is_show": '1',
        }
      }
    }
  }
}

export {
  defaultData
}