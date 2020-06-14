<template>
  <Layout ref="load" class="pay-guide" v-if="$store.state.isWeixin">
    <Navbar :isMenu="false" :isShowLeft="false" />
    <div class="wrap">
      <img :src="$BASEIMGPATH + 'icon_indicate.png'" class="indicate" />
      <div class="text">
        请在菜单中选择在浏览器中打开，
        <br />以完成支付
      </div>
    </div>
    <div class="iphone" v-if="iosShow">
      <img :src="$BASEIMGPATH + 'safari_icon_small.png'" class="img" />
      <div>在Safari中打开</div>
    </div>
    <div class="android" v-else>
      <img :src="$BASEIMGPATH + 'android_icon_small.png'" class="img" />
    </div>
    <div class="btn-wrap">
      <van-button round type="danger" size="normal" @click="onResult">已支付完成</van-button>
      <van-button plain round type="danger" size="normal" @click="onBack">其他支付方式</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { isIos } from "@/utils/util";
import { _decode } from "@/utils/base64";
export default sfc({
  name: "pay-guide",
  data() {
    return {
      iosShow: false,
      out_trade_no: null
    };
  },
  mounted() {
    let param = JSON.parse(_decode(this.$route.query.param));
    const div = document.createElement("div");
    div.innerHTML = param;
    document.body.appendChild(div);
    this.out_trade_no = JSON.parse(
      document.querySelector("input[name='biz_content']").value
    ).out_trade_no;

    if (this.$store.state.isWeixin) {
      if (isIos()) {
        this.iosShow = true;
      } else {
        this.iosShow = false;
      }
    } else {
      document.forms[0].submit();
    }
    this.$refs.load.success();
  },
  methods: {
    onResult() {
      if (this.out_trade_no) {
        this.$router.replace({
          name: "pay-result",
          query: {
            out_trade_no: this.out_trade_no
          }
        });
      }
    },
    onBack() {
      let link = `/pay/payment?${this.$route.query.real}`;
      this.$router.replace(link);
    }
  }
});
</script>

<style scoped>
.wrap {
  overflow: hidden;
  margin-top: 10px;
}
.indicate {
  width: 40px;
  float: right;
  margin-right: 15px;
  height: 50px;
}
.text {
  font-size: 14px;

  line-height: 20px;
  float: right;
  margin-top: 38px;
}
.iphone {
  width: 160px;
  height: 160px;
  margin: auto;
  display: block;
  background-color: #f0eff4;
  border-radius: 50%;
  overflow: hidden;
  text-align: center;
  margin-top: 20px;
  font-size: 12px;
}
.iphone .img {
  width: 70px;
  height: 70px;
  margin: 30px auto 10px;
  display: block;
}
.android {
  margin: auto;
  display: block;
  overflow: hidden;
  width: 160px;
  margin-top: 20px;
}
.android .img {
  width: 100%;
  height: 100%;
  display: block;
}
.btn-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 20px;
}
.btn-wrap >>> .van-button:first-child {
  margin-right: 20px;
  width: 116px;
}
</style>