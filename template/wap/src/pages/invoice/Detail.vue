<template>
  <Layout ref="load" class="invoice-detail bg-f8">
    <Navbar />
    <div class="card-group-box">
      <div class="img">
        <img :src="img_path" :onerror="$ERRORPIC.noGoods" @click="onPreview"/>
      </div>
      <van-button
        round
        class="btn-add"
        size="normal"
        type="danger"
        v-if="auth_url"
        :url="auth_url"
      >添加至微信卡包</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_IMG, GET_AUTHURL } from "@/api/invoice";
import { ImagePreview } from "vant";
export default sfc({
  name: "invoice-detail",
  data() {
    return {
      params: {},
      img_path: "",

      auth_url: ""
    };
  },
  computed: {
    order_no() {
      return this.$route.query.order_no;
    },
    is_upload() {
      return this.$route.query.is_upload;
    }
  },
  mounted() {
    if (this.$store.state.config.addons.invoice) {
      this.$refs.load.success();
      this.loadData();
    } else {
      this.$refs.load.fail({
        errorText: "未开启发票助手应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      $this.params.order_no = $this.order_no;
      GET_IMG($this.params).then(({ data }) => {
        if (data.data.substring(0, 4) == "http") {
          $this.img_path = data.data;
        } else {
          $this.img_path = `${$this.$store.state.domain}/${data.data}`;
        }
      });

      let param = {
        order_no: $this.order_no,
        source: $this.$store.state.isWeixin ? "web" : "wap"
      };
      GET_AUTHURL(param).then(({ data }) => {
        $this.auth_url = data.data;
      });
    },
    onPreview() {
      if(this.auth_url){
      ImagePreview({
        images: [this.auth_url]
      });
      }
    },
  }
});
</script>

<style scoped>

.img {
  position: relative;
}
.img img {
  width: 100%;
  height: auto;
}
.btn-add {
  margin: 20px auto;
  display: block;
}
</style>