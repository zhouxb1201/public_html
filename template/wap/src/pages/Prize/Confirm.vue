<template>
  <Layout ref="load" class="prize-confirm bg-f8">
    <Navbar :isMenu="false" />
    <ChoiceAddress
      :address="address"
      @select="onAddress"
      v-if="(type == 5 && goods_type == 1) || type == 6"
    />

    <div class="store-wrap" @click="onPopupStore" v-if="type == 5 && goods_type == 0">
      <van-cell
        border
        center
        is-link
        class="card-bottom-bg"
        icon="add"
        v-if="!storeInfo.store_name"
      >
        <div class="store-info">添加门店</div>
      </van-cell>
      <van-cell border center is-link class="card-bottom-bg" v-else>
        <div class="store-info">
          <van-col span="14">使用门店</van-col>
          <van-col span="10" class="text-right">{{storeInfo.store_name || ''}}</van-col>
        </div>
      </van-cell>
    </div>

    <div class="confrim-wrap">
      <div class="confrim-left">
        <div class="img">
          <img :src="items.pic" :onerror="defaultImg(items.type)" />
        </div>
        <div class="confrim-goods-info">
          <div>{{items.prize_name}}</div>
          <div>{{items.name}}</div>
        </div>
      </div>
      <div class="confrim-right">x1</div>
    </div>

    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onReceive">立即领取</van-button>
    </div>

    <PopupStore
      v-if="$store.state.config.addons.store"
      v-model="store_show"
      :store_id="storeInfo.store_id || storeInfo.card_store_id"
      :list="store_list || []"
      @select="onStore"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ChoiceAddress from "@/components/ChoiceAddress";
import PopupStore from "@/components/PopupStore";
import { GET_PRIZEDETAIL, GET_ACCEPTPRIZE } from "@/api/prize";
import { isEmpty } from "@/utils/util";
import { _decode } from "@/utils/base64";
export default sfc({
  name: "prize-confirm",
  data() {
    const params = JSON.parse(_decode(this.$route.query.params));
    return {
      address: {},

      type: null, //奖品类型 1=>余额 2=>积分 3=>优惠券 4=>礼品券 5=>商品 6=>赠品

      items: {},

      flag: true, //防止重复点击

      store_show: false,

      goods_type: null, //0 => 计时计次商品 1 => 普通商品 3 => 虚拟商品

      store_list: [],
      storeInfo: {}
    };
  },
  computed: {
    // 请求参数
    params: {
      get() {
        return JSON.parse(_decode(this.$route.query.params));
      },
      set(e) {
        console.log(e);
      }
    },
    defaultImg(type) {
      let imgSrc = null;
      let path = this.$BASEIMGPATH;
      return type => {
        if (type) {
          if (type == 1) {
            // 1 => 余额
            imgSrc = 'this.src="' + path + "default-balance.png" + '"';
          } else if (type == 2) {
            // 2 => 积分
            imgSrc = 'this.src="' + path + "default-integral.png" + '"';
          } else if (type == 3) {
            // 3 => 优惠券
            imgSrc = 'this.src="' + path + "default-coupon.png" + '"';
          } else if (type == 4) {
            // 4 => 礼品券
            imgSrc = 'this.src="' + path + "default-giftvoucher.png" + '"';
          } else if (type == 5) {
            // 5 => 商品
            imgSrc = 'this.src="' + path + "default-goods.png" + '"';
          } else if (type == 6) {
            // 6 => 赠品
            imgSrc = 'this.src="' + path + "default-gift.png" + '"';
          }
        }
        return imgSrc;
      };
    },
    //计时计次商品
    isOfflineGoods() {
      let flag = false;
      if (this.type == 5 && this.goodsType == 0) {
        flag = true;
      }
      return flag;
    }
  },
  mounted() {
    this.getLocation().then(() => {
      this.loadData();
    });
  },
  methods: {
    getLocation() {
      return new Promise((resolve, reject) => {
        this.$store
          .dispatch("getBMapLocation")
          .then(({ location }) => {
            this.params.lng = location.lng;
            this.params.lat = location.lat;
            resolve();
          })
          .catch(error => {
            this.$Toast(error);
            resolve();
          });
      });
    },
    loadData() {
      const $this = this;
      GET_PRIZEDETAIL($this.params).then(({ data }) => {
        $this.items = data;
        $this.type = data.type;
        $this.goods_type = data.goods_type;
        if (!isEmpty(data.address)) {
          $this.address = {
            name: data.address.consigner,
            tel: data.address.mobile,
            id: data.address.address_id,
            address:
              data.address.province_name +
              data.address.city_name +
              data.address.district_name +
              data.address.address_detail
          };
        }
        if (!isEmpty(data.store_list)) {
          $this.store_list = data.store_list;
        }
        $this.$refs.load.success();
      });
    },
    onAddress(address) {
      this.address = address;
      this.params.address_id = address.id;
      this.loadData();
    },
    onReceive() {
      if (!this.flag) {
        return false;
      }
      this.flag = false; //防止重复点击
      let param = {};
      param.member_prize_id = this.items.member_prize_id;
      param.order_from =
        this.$store.state.isWeixin && this.$store.getters.config.is_wchat
          ? 1
          : 2;
      if ((this.type == 5 && this.goods_type == 1) || this.type == 6) {
        param.address_id = this.items.address.address_id;
        if (!param.address_id) {
          this.flag = true;
          return this.$Toast("请选择收货地址!");
        }
      } else if (this.type == 5 && this.goods_type == 0) {
        param.card_store_id = this.storeInfo.card_store_id
          ? this.storeInfo.card_store_id
          : this.storeInfo.store_id;
        if (!param.card_store_id) {
          this.flag = true;
          return this.$Toast("请选择门店!");
        }
      }
      GET_ACCEPTPRIZE(param)
        .then(res => {
          if (res.code == 1) {
            this.$router.replace({ name: "prize-result" });
          }
        })
        .catch(() => {});
    },
    onPopupStore() {
      this.store_show = true;
    },
    // 选择门店
    onStore({ lat, lng, shop_id, store_id, store_name }) {
      this.params.lng = lng;
      this.params.lat = lat;
      if (this.items.shop_id == shop_id) {
        if (this.isOfflineGoods) {
          this.storeInfo.card_store_id = store_id;
        } else {
          this.storeInfo.store_id = store_id;
        }
        this.storeInfo.store_name = store_name;
        this.storeInfo.shop_id = shop_id;
      }
      this.loadData();
    }
  },
  components: {
    ChoiceAddress,
    PopupStore
  }
});
</script>

<style scoped>
.confrim-wrap {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  position: relative;
  min-height: 70px;
  background-color: #fff;
  padding: 10px;
}

.confrim-left {
  flex: 1;
  display: flex;
  display: -webkit-box;
  display: -ms-flexbox;
}
.confrim-left .img {
  width: 60px;
  height: 60px;
  position: relative;
  overflow: hidden;
}
.confrim-left .img img {
  width: 100%;
  height: auto;
}
.confrim-goods-info {
  flex: 1;
  padding-left: 10px;
  font-size: 14px;
}
.confrim-goods-info div:last-child {
  color: #b5b5b5;
  margin-top: 4px;
}
.confrim-right {
  color: #fd5b5b;
  font-size: 14px;
}
.btn-receive {
  left: 0;
  bottom: 0;
  width: 100%;
  z-index: 100;
  position: fixed;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.btn-receive-wrap {
  height: 50px;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  font-size: 14px;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  background-color: #fff;
}
.action-btn {
  font-size: 14px;
  height: 40px;
  line-height: 38px;
  margin: 5px 10px;
  border-radius: 8px;
}
.mt-10 {
  margin-top: 10px;
}
.cell-right-text >>> .van-cell__value {
  font-size: 12px;
  color: #909399;
}
.store-info {
  font-size: 14px;
  font-weight: 500;
  line-height: 40px;
  overflow: hidden;
}
.card-bottom-bg::before {
  content: "";
  left: 0;
  right: 0;
  bottom: 0;
  height: 2px;
  position: absolute;
  background: -webkit-repeating-linear-gradient(
    135deg,
    #ff6c6c 0,
    #ff6c6c 20%,
    transparent 0,
    transparent 25%,
    #3283fa 0,
    #3283fa 45%,
    transparent 0,
    transparent 50%
  );
  background: repeating-linear-gradient(
    -45deg,
    #ff6c6c 0,
    #ff6c6c 20%,
    transparent 0,
    transparent 25%,
    #3283fa 0,
    #3283fa 45%,
    transparent 0,
    transparent 50%
  );
  background-size: 80px;
}
.card-bottom-bg >>> .van-cell__left-icon {
  font-size: 30px;
  line-height: 20px;
  color: #3283fa;
}
</style>


      