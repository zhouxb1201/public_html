<template>
  <Layout ref="load" class="anticounterfeiting-result">
    <Navbar />
    <Card
      :id="goods.goods_id"
      :image="goods.pic_img"
      :name="goods.name"
      :price="goods.price"
      :state="goods.state"
    />
    <van-cell-group class="card-group-box">
      <van-cell title="防伪信息" />
      <van-cell>
        <div slot="title">
          <span class="custom-text">{{info.search_tips}}</span>
        </div>
        <div slot="label">
          <span class="custom-text">防伪编码：{{info.anti_code}}</span>
        </div>
        <div slot="label" v-if="info.box_code">
          <span class="custom-text">箱码：{{info.box_code}}</span>
        </div>
        <div slot="label" v-if="info.address">
          <span class="custom-text">销售地址：{{info.address}}</span>
        </div>
      </van-cell>
    </van-cell-group>
    <van-cell-group class="card-group-box" v-if="info.batch_trace != ''">
      <van-cell title="商品溯源信息" />
      <van-steps direction="vertical" :active="0" active-color="#f44">
        <van-step class="step-wrap" v-for="(item , index) in info.batch_trace" :key="index">
          <div class="step-head">
            <span class="title">{{item.node_name}}</span>
            <span class="time">{{ item.create_time | formatDate("s") }}</span>
          </div>
          <div class="step-content">{{item.node_description}}</div>
          <div class="step-img">
            <div v-for="(child,cindex) in item.proof_pic" :key="cindex" class="img">
              <img :src="child" :onerror="$ERRORPIC.noGoods" />
            </div>
          </div>
        </van-step>
      </van-steps>
    </van-cell-group>
    <div class="btn-info" v-if="info.chain_status == 1">
      <van-button type="danger" size="normal" @click="onChain">查看上链信息</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import Card from "./component/Card";
import { Step, Steps } from "vant";
import { GET_SEARCHANTI } from "@/api/anticounterfeiting";
export default sfc({
  name: "anticounterfeiting-result",
  data() {
    return {
      info: {},
      goods: {}
    };
  },
  computed: {
    anti_code() {
      return this.$route.query.anti_code ? this.$route.query.anti_code : "";
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const that = this;
      let params = {};
      that.$store.dispatch("getBMapLocation").then(({ location }) => {
        params.lat = location.lat;
        params.lng = location.lng;
        params.anti_code = that.anti_code;
        GET_SEARCHANTI(params)
          .then(({ data }) => {
            that.info = data;
            that.goods = {
              goods_id: data.goods_id,
              pic_img: data.goods_img,
              name: data.goods_name,
              price: data.price,
              state: data.state
            };
            that.$refs.load.success();
          })
          .catch(err => {
            that.$refs.load.fail();
          });
      });
    },
    onChain() {
      window.location.href = this.info.chain_url;
    }
  },
  components: {
    Card,
    [Step.name]: Step,
    [Steps.name]: Steps
  }
});
</script>

<style scoped>
.card-group-box {
  margin: 10px;
  border-radius: 10px;
  overflow: hidden;
  -webkit-box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
}
.card-group-box >>> .van-cell:not(:last-child)::after {
  left: 0;
}
.step-head {
  display: flex;
  justify-content: space-between;
  color: #333;
  font-size: 12px;
}
.step-head .title {
  font-size: 14px;
}
.step-head .time {
  color: #999999;
  font-size: 12px;
}
.step-content {
  color: #999999;
  font-size: 14px;
  padding-top: 10px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}
.step-img {
  display: flex;
  padding: 10px 0px;
  overflow-x: scroll;
  width: 100%;
}
.step-img .img {
  display: inline-block;
  width: 80px;
  height: 80px;
  border-radius: 2px;
  flex-shrink: 1;
  margin-right: 8px;
}
.step-img .img img {
  width: 80px;
  height: 80px;
  border-radius: 2px;
}
.btn-info {
  padding: 20px;
}
.btn-info >>> .van-button {
  width: 100%;
  border-radius: 20px;
}
</style>