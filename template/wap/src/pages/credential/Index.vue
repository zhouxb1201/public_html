<template>
  <Layout ref="load" class="credential-index bc-fff">
    <Navbar />
    <HeadBanner :src="img_path ? img_path : $BASEIMGPATH +  'pic_warrant.png'" :link="img_link" />
    <div class="logo-wrap">
      <!--<img :src="logo" :onerror="$ERRORPIC.noGoods" class="img" />-->
      <p class="text">授权证书查询</p>
    </div>
    <div class="polling-wrap">
      <input type="text" placeholder="请输入证书编号" v-model="input_val" />
      <van-button type="info" size="normal" @click="onPollResult">查询</van-button>
    </div>
    <div class="tips">
      <h4>证书查询方法：</h4>
      <p>1.输入证书编号，点击查询；</p>
      <h4>查询结果说明：</h4>
      <p>1.如果该证书编号正确，且授权信息与证书信息一致，则表示授权证书真实</p>
      <p>2.如果该证书编号正确，但授权信息与证书信息不一致，则表示授权证书伪造</p>
      <p>3.如果证书编号错误，则说明该证书未经官方授权。</p>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_SEARCHCREDENTIAL } from "@/api/credential";
import HeadBanner from "@/components/HeadBanner";
export default sfc({
  name: "credential-index",
  data() {
    return {
      img_path: "",
      img_link: "",
      logo: "",
      input_val: ""
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const that = this;
      GET_SEARCHCREDENTIAL()
        .then(res => {
          if (res.code >= 0) {
            that.img_path = res.data.banner_list.img_path;
            that.img_link = res.data.banner_list.img_link;
            that.logo = res.data.logo;
          } else {
            that.$Toast(res.message);
          }
          that.$refs.load.success();
        })
        .catch(err => {
          that.$refs.load.fail();
        });
    },
    onPollResult() {
      const that = this;
      if (!that.input_val) {
        that.$Toast("请输入证书编号！");
        return false;
      }
      that.$router.push({
        name: "credential-result",
        query: {
          cred_no: that.input_val
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
</style>