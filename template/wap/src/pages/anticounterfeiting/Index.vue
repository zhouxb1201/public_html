<template>
  <Layout ref="load" class="anticounterfeiting-index bc-fff">
    <Navbar />
    <HeadBanner :src="img_path" :link="img_link" />
    <div class="logo-wrap">
      <!--<img :src="logo" :onerror="$ERRORPIC.noGoods" class="img" />-->
      <p class="text">防伪溯源商品查询</p>
    </div>
    <div class="polling-wrap">
      <div class="text-content">
        <input type="text" placeholder="请输入防伪码" v-model="input_val" />
        <div class="scan" @click="onScan">
          <van-icon name="scan" size="24px" />
        </div>
      </div>
      <van-button type="info" size="normal" @click="onPollResult">查询</van-button>
    </div>
    <div class="tips">
      <h4>防伪溯源查询方法：</h4>
      <p>1.输入商品防伪标签中的防伪溯源码，点击查询；</p>
      <p>2.点击输入框右侧的扫一扫图标，扫描防伪二维码；</p>
      <h4>查询结果说明：</h4>
      <p>1.如果该防伪码首次被查询，则说明是正品；</p>
      <p>2.如果该二维码被查询过，若非本人所为，则说明可能是伪劣假冒商品；</p>
      <p>3.如果防伪码错误，则说明该商品未经官方验证。</p>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ISLOGIN } from "@/api/anticounterfeiting";
import { setSession } from "@/utils/storage";
import HeadBanner from "@/components/HeadBanner";
export default sfc({
  name: "anticounterfeiting-index",
  data() {
    return {
      img_path: "",
      img_link: "",
      logo: "",
      input_val: ""
    };
  },
  mounted() {
    this.$refs.load.success();
    this.loadData();
  },
  methods: {
    loadData() {
      const that = this;
      GET_ISLOGIN().then(res => {
        if (res.code == 1) {
          //需要登录
          if (!that.$store.getters.token) {
            setSession("toPath", that.$router.currentRoute.fullPath);
            that.$router.replace({ name: "login" });
          }
        }
        that.img_path = res.data.advert_pic ? res.data.advert_pic : $BASEIMGPATH + 'pic_legal.png';
        that.logo = res.data.logo ? res.data.logo : "";
        that.img_link = res.data.advert_pic_link
          ? res.data.advert_pic_link
          : "";
      });
    },
    onPollResult() {
      const that = this;
      if (!that.input_val) {
        that.$Toast("请输入防伪码！");
        return false;
      }
      that.$router.push({
        name: "anticounterfeiting-result",
        query: {
          anti_code: that.input_val
        }
      });
    },
    onScan() {
      const that = this;
      that.$store.dispatch("wxScanQRCode").then(res => {
        if (res.indexOf(",") !== -1) {
          let r = res.split(",");
          let first = r[1];
          if (first) {
            that.$router.push({
              name: "anticounterfeiting-result",
              query: {
                anti_code: first
              }
            });
          } else {
            that.$Dialog
              .alert({
                message: "防伪码错误，请核对后重试!"
              })
              .then(() => {});
          }
        } else {
          if (res) {
            that.$router.push({
              name: "anticounterfeiting-result",
              query: {
                anti_code: res
              }
            });
          } else {
            that.$Dialog
              .alert({
                message: "防伪码错误，请核对后重试!"
              })
              .then(() => {});
          }
        }
      });
    }
  },
  components: {
    HeadBanner
  }
});
</script>

<style scoped>
.bc-fff {
  background-color: #fff;
}
.logo-wrap {
  margin-top: 20px;
}
.logo-wrap .img {
  display: block;
  margin: auto;
  width: 80px;
  height: 80px;
  border-radius: 2px;
}
.logo-wrap .text {
  text-align: center;
  margin-top: 35px;
  font-size: 16px;
}
.polling-wrap {
  margin: 16px;
}
.text-content {
  width: 100%;
  position: relative;
}
.polling-wrap input {
  border: 1px solid #ebebeb;
  border-radius: 5px;
  height: 40px;
  line-height: 40px;
  font-size: 14px;
  text-indent: 6px;
  width: 100%;
  display: block;
  box-shadow: 0px 0px 3px #ebebeb;
  background-color: #eeeeee;
}
.polling-wrap >>> .van-button {
  width: 100%;
  margin-top: 20px;
  border-radius: 10px;
}
.tips {
  margin: 16px;
  font-size: 12px;
  color: #999999;
  line-height: 20px;
}
.tips h4 {
  font-weight: normal;
}
.scan {
  height: 100%;
  position: absolute;
  top: 0;
  right: 0;
  width: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>