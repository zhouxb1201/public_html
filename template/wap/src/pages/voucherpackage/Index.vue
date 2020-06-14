<template>
  <Layout ref="load" class="voucherpackage">
    <Navbar :title="navbarTitle" />
    <div class="bg" :style="bgStyle">
      <transition name="van-fade" mode="out-in">
        <div class="img-bg" :style="backgroundImg" v-if="!isSuccess">
          <div class="img">
            <img :src="$BASEIMGPATH+'voucherpackage-bg-02.png'" />
            <div class="name">{{detail.voucher_package_name}}</div>
            <div class="btn-box">
              <van-button
                class="btn"
                round
                type="danger"
                block
                @click="bindMobile('onReceive')"
                :loading="isLoading"
                loading-text="领取中..."
              >领取</van-button>
            </div>
          </div>
        </div>
        <SuccessBox
          :name="detail.voucher_package_name"
          :couponList="couponList"
          :giftList="giftList"
          v-else
        />
      </transition>
      <div class="cell-group">
        <div class="cell">
          <div>
            <span class="tag">活动时间</span>
          </div>
          <div>{{detail.start_time | formatDate('s')}} ~ {{detail.end_time | formatDate('s')}}</div>
        </div>
        <div class="cell">
          <div>
            <span class="tag">活动说明</span>
          </div>
          <div>{{detail.desc}}</div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import {
  GET_VOUCHERPACKAGEDETAIL,
  RECEIVE_VOUCHERPACKAGE
} from "@/api/voucherpackage";
import SuccessBox from "./component/SuccessBox";
import { bindMobile } from "@/mixins";
export default sfc({
  name: "voucherpackage",
  data() {
    return {
      isSuccess: false,
      isLoading: false,
      detail: {},
      couponList: [],
      giftList: []
    };
  },
  mixins: [bindMobile],
  computed: {
    navbarTitle() {
      let title = this.detail.voucher_package_name;
      if (title) document.title = title;
      return title;
    },
    bgStyle() {
      return {
        minHeight: this.$store.state.isWeixin ? "100%" : "calc(100% - 46px)"
      };
    },
    backgroundImg() {
      return {
        backgroundImage:
          "url(" + this.$BASEIMGPATH + "voucherpackage-bg-01.png)"
      };
    }
  },
  mounted() {
    if (this.$store.state.config.addons.voucherpackage) {
      this.loadData();
    } else {
      this.$refs.load.fail({
        errorText: "未开启券包应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      const id = $this.$route.params.id;
      GET_VOUCHERPACKAGEDETAIL(id)
        .then(({ data }) => {
          $this.detail = data;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onReceive() {
      const $this = this;
      const id = $this.$route.params.id;
      $this.isLoading = true;
      RECEIVE_VOUCHERPACKAGE(id)
        .then(({ data }) => {
          $this.couponList = data.coupon_type_list;
          $this.giftList = data.gift_voucher_list;
          $this.$Toast.success("领取成功");
          setTimeout(() => {
            $this.isLoading = false;
            $this.isSuccess = true;
          }, 500);
        })
        .catch(() => {
          $this.isLoading = false;
        });
    }
  },
  components: {
    SuccessBox
  }
});
</script>

<style scoped>
.bg {
  position: absolute;
  width: 100%;
  background: linear-gradient(#fe2023 20%, #f54244 30%);
}

.img-bg {
  width: 100%;
  height: auto;
  min-height: 300px;
  position: relative;
  padding-top: 130px;
  background-size: contain;
  background-position: top center;
  background-repeat: no-repeat;
}

.img-bg .img {
  width: 84%;
  height: auto;
  margin: 0 auto;
  position: relative;
}

.img-bg img {
  width: 100%;
  height: auto;
  display: block;
}

.img-bg .img .name {
  font-size: 20px;
  color: #ff454e;
  text-align: center;
  position: absolute;
  top: 0;
  width: 100%;
  font-size: 26px;
  font-weight: 800;
  padding: 40px 0;
}

.img-bg .img .btn-box {
  position: absolute;
  width: 100%;
  bottom: 0;
  padding: 20px 30px;
}

.img-bg .img .btn-box .btn {
  color: #fff;
}

.cell-group {
  width: 84%;
  margin: 30px auto;
}

.cell-group .cell {
  color: #fff;
  margin-bottom: 10px;
  line-height: 1.6;
}

.cell-group .cell .tag {
  padding: 8px 16px;
  border-radius: 20px;
  color: #ff4444;
  background: #feda6f;
  display: inline-block;
  font-size: 12px;
  margin-bottom: 4px;
  line-height: 1;
}
</style>
