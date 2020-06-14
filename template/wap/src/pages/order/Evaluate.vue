<template>
  <Layout ref="load" class="order-evaluate bg-f8">
    <Navbar />
    <div class="evaluate">
      <van-cell icon="shop-o" :title="order_info.shop.shop_name" :border="false" />
      <van-cell-group class="items" v-for="(item,index) in order_info.goods" :key="index">
        <van-cell>
          <van-row type="flex">
            <div class="img">
              <img :src="item.img" :onerror="$ERRORPIC.noGoods" />
            </div>
            <div class="praise" v-if="!isAgain">
              <van-col
                class="item"
                :class="item.score === child.score ? 'active' : ''"
                v-for="(child,i) in praiseArr"
                :key="i"
                @click.native="onPraise(index,child.score)"
              >
                <van-icon :name="'v-icon-'+child.icon" />
                <span>{{child.text}}</span>
              </van-col>
            </div>
            <div class="name" v-else>{{item.name}}</div>
          </van-row>
        </van-cell>
        <van-field
          type="textarea"
          placeholder="分享你购买到此商品的想法与心情"
          rows="4"
          autosize
          v-model="item.evaluate"
        />
        <van-cell>
          <van-row type="flex" justify="space-between" class="upload-img">
            <ImagePanelPreview class="img-box" :list="item.arrImg" />
            <UploadImages
              :name="String(index)"
              :total="item.arrImg.length"
              multiple
              type="evaluate"
              @finish="onUploadFinish"
            />
          </van-row>
        </van-cell>
      </van-cell-group>
    </div>
    <div class="score" v-if="!isAgain && $store.state.config.addons.shop">
      <van-cell-group>
        <van-cell icon="shop-o" title="店铺评分" />
        <van-cell>
          <van-row type="flex" class="item">
            <div class="text">描述相符</div>
            <van-rate v-model="shop_desc" />
          </van-row>
          <van-row type="flex" class="item">
            <div class="text">物流服务</div>
            <van-rate v-model="shop_stic" />
          </van-row>
          <van-row type="flex" class="item">
            <div class="text">服务态度</div>
            <van-rate v-model="shop_service" />
          </van-row>
        </van-cell>
      </van-cell-group>
    </div>
    <div class="score" v-if="!isAgain && isStore">
      <van-cell-group>
        <van-cell icon="shop-o" title="门店评分" />
        <van-cell>
          <van-row type="flex" class="item">
            <div class="text">服务态度</div>
            <van-rate v-model="store_service" />
          </van-row>
        </van-cell>
      </van-cell-group>
    </div>
    <div class="foot-btn-group">
      <van-button size="normal" round type="danger" block @click="onSubmit" :loading="isLoading">提交</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { Rate } from "vant";
import UploadImages from "@/components/UploadImages";
import ImagePanelPreview from "@/components/ImagePanelPreview";
import { _decode } from "@/utils/base64";
import { ADD_ORDEREVALUATE, ADD_ORDERAGAINEVALUATE } from "@/api/order";
import { UPLOAD_IMAGES } from "@/api/config";
export default sfc({
  name: "order-evaluate",
  data() {
    return {
      order_info: {},
      praiseArr: [
        {
          text: "好评",
          icon: "praise",
          score: 5,
          checked: true
        },
        {
          text: "中评",
          icon: "review",
          score: 3,
          checked: false
        },
        {
          text: "差评",
          icon: "review",
          score: 1,
          checked: false
        }
      ],

      shop_desc: 0,
      shop_service: 0,
      shop_stic: 0,

      store_service: 0, //门店评分

      isLoading: false
    };
  },
  computed: {
    isAgain() {
      return this.$route.hash && this.$route.hash == "#again" ? true : false;
    },
    isStore() {
      return this.order_info.store_id ? true : false;
    }
  },
  created() {
    this.order_info = JSON.parse(_decode(this.$route.query.order_info));
  },
  mounted() {
    setTimeout(() => {
      this.$refs.load.success();
    }, 500);
  },
  methods: {
    onUploadFinish({ src }, index) {
      this.order_info.goods[parseInt(index)].arrImg.push(src);
    },
    onPraise(index, score) {
      this.order_info.goods[index].score = score;
    },
    onSubmit() {
      const $this = this;
      const list = $this.order_info.goods;
      let goods_evaluate = [];
      list.forEach(e => {
        let obj = {};
        obj.order_goods_id = e.id;
        obj.content = e.evaluate;
        if (!$this.isAgain) obj.explain_type = e.score;
        obj.images = e.arrImg;
        goods_evaluate.push(obj);
      });
      const params = {};
      params.order_id = $this.$route.params.orderid;
      params.goods_evaluate = goods_evaluate;
      if (!$this.isAgain && $this.shop_desc) params.shop_desc = $this.shop_desc;
      if (!$this.isAgain && $this.shop_service)
        params.shop_service = $this.shop_service;
      if (!$this.isAgain && $this.shop_stic) params.shop_stic = $this.shop_stic;
      if (!$this.isAgain && $this.isStore && $this.store_service)
        params.store_service = $this.store_service;
      // console.log(params);
      // return;
      $this.isLoading = true;
      if (!$this.isAgain) {
        ADD_ORDEREVALUATE(params)
          .then(({ message }) => {
            $this.$Toast.success(message);
            $this.$router.replace({
              name: "order-detail",
              params: {
                orderid: $this.$route.params.orderid
              }
            });
          })
          .catch(() => {
            $this.isLoading = false;
          });
      } else {
        ADD_ORDERAGAINEVALUATE(params)
          .then(({ message }) => {
            $this.$Toast.success(message);
            $this.$router.replace({
              name: "order-detail",
              params: {
                orderid: $this.$route.params.orderid
              }
            });
          })
          .catch(() => {
            $this.isLoading = false;
          });
      }
    }
  },
  components: {
    UploadImages,
    ImagePanelPreview,
    [Rate.name]: Rate
  }
});
</script>

<style scoped>
.items {
  margin-bottom: 10px;
}

.order-evaluate >>> .evaluate,
.order-evaluate >>> .score {
  margin-bottom: 10px;
}

.order-evaluate >>> .evaluate .img {
  width: 50px;
  height: 50px;
  margin-right: 10px;
}

.order-evaluate >>> .evaluate .img img {
  width: 100%;
  height: 100%;
  background: #f9f9f9;
}

.order-evaluate >>> .evaluate .praise {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}

.order-evaluate >>> .evaluate .name {
  display: flex;
  align-items: center;
  flex: 1;
  line-height: 20px;
  overflow: hidden;
  text-overflow: ellipsis;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.order-evaluate >>> .evaluate .praise .item {
  color: #909399;
  flex: 1;
  text-align: center;
}

.order-evaluate >>> .evaluate .praise .item.active {
  color: #ff454e;
}

.order-evaluate >>> .score .item {
  align-items: center;
  padding: 5px 0;
}

.order-evaluate >>> .score .item .text {
  margin-right: 10px;
  height: 22px;
  line-height: 22px;
}

.order-evaluate >>> .score .van-rate {
  height: 22px;
}

.upload-img {
  overflow: hidden;
  display: flex;
}

.upload-img .img-box {
  flex: 1;
  margin-right: 10px;
}
</style>
