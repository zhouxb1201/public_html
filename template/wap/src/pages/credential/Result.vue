<template>
  <Layout ref="load" class="credential-result bc-fff">
    <Navbar />
    <div class="wrap">
      <van-cell-group class="cell-group">
        <van-cell :title="item.name" :value="item.val" v-for="(item,index) in info" :key="index" />
      </van-cell-group>
    </div>
    <div class="img">
      <img :src="img_path" :onerror="$ERRORPIC.noGoods" />
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_CREDENTIALRESULT } from "@/api/credential";
export default sfc({
  name: "credential-result",
  data() {
    return {
      credInfo: {},
      img_path: ""
    };
  },
  computed: {
    cred_no() {
      return this.$route.query.cred_no;
    },
    info() {
      const that = this;
      let arr = [];
      arr.push(
        { name: "证书编号：", val: that.credInfo.cred_no },
        { name: "证书名称：", val: that.credInfo.cred_name },
        { name: "证书类型：", val: that.credInfo.cred_type },
        { name: "授权人：", val: that.credInfo.mall_name },
        { name: "被授权人昵称：", val: that.credInfo.nickname },
        { name: "被授权人微信号：", val: that.credInfo.wchat_name },
        { name: "被授权人手机号：", val: that.credInfo.user_tel },
        { name: "授权时间：", val: that.credInfo.create_date }
      );

      return arr;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const that = this;
      GET_CREDENTIALRESULT(that.cred_no)
        .then(({ data }) => {
          that.credInfo = data;
          if (data.image_path.substring(0, 4) == "http") {
            that.img_path = data.image_path;
          } else {
            that.img_path = `${that.$store.state.domain}/${data.image_path}`;
          }
          that.$refs.load.success();
        })
        .catch(err => {
          that.$Dialog
            .alert({
              message: "授权证书编号错误，请核对后重试!"
            })
            .then(() => {});
          that.$router.back();
        });
    }
  }
});
</script>

<style scoped>
.bc-fff {
  background-color: #fff;
}
.cell-group >>> .van-cell__title,
.van-cell__value {
  flex: none;
}
.img {
  margin: 10px 15px 20px 15px;
}

.img img {
  width: 100%;
  display: block;
}
.cell-group >>> .van-cell:not(:last-child)::after {
  border-bottom: none;
}
.wrap >>> .van-hairline--top-bottom::after {
  border-width: 0;
}
</style>