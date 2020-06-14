<template>
  <div class="content">
    <PostHeader :info="info" :pageType="pageType" />

    <!--等级-->
    <van-cell title="等级" class="cell-panel" v-if="pageType == 3">
      <span class="tag active">{{gradeInfo.level_name}}</span>
    </van-cell>
    <van-cell title="等级" class="cell-panel" v-else-if="pageType == 4">
      <span
        :class="active == index ? 'tag active' : 'tag' "
        v-for="(tags,index) in gradeInfo"
        :key="index"
        @click="getItem(index)"
      >{{tags.level_name}}</span>
    </van-cell>

    <!--等级商品信息-->
    <PostGoodsGroup :items="goodslist" class="cell-group" @click="onGoods" />

    <PostGoodsInfo
      :goods_detail="goods_detail"
      class="cell-group"
      :orderType="pageType"
      ref="childgoodsinfo"
    />
    <PostBenefit class="cell-group" />
    <PostGoodsDescript :descript="goods_detail.description" class="cell-group" />

    <div class="foot-btn">
      <van-button type="danger" round block @click="onSubmit">
        <div class="btn-flex-column">
          <div>{{btnText}}</div>
          <div class="goods-name">
            已选择："
            <span class="text-nowrap">{{goods_detail.goods_name}}</span>"
          </div>
        </div>
      </van-button>
    </div>
  </div>
</template>

<script>
import { GET_GOODSDETAIL } from "@/api/goods";
import PostHeader from "./post/Header";
import PostGoodsGroup from "./post/GoodsGroup";
import PostGoodsInfo from "./post/GoodsInfo";
import PostBenefit from "./post/Benefit";
import PostGoodsDescript from "./post/GoodsDescript";
import { GET_RENEW, GET_UPGRADE } from "@/api/microshop";
export default {
  data() {
    return {
      goods_detail: {},

      gradeInfo: {}, //等级信息
      active: 0, //切换等级
      goodslist: [],

      goodsParams: {
        goods_id: null,
        mic_goods: 1
      },

      gradeGoods: [],
      gradeFlag: true
    };
  },
  props: {
    /**
     * 类型
     * 2 ==> 申请成为店主
     * 3 ==> 续费
     * 4 ==> 升级
     */
    pageType: {
      type: [String, Number],
      required: true
    },
    // 店主信息
    info: Object,
    goods: Array
  },
  watch: {
    pageType(tp) {
      this.tp = this.pageType;
      this.orderType = this.pageType;
    }
  },
  computed: {
    btnText() {
      let text = "";
      if (this.pageType == 2) {
        text = "立即开店";
      } else if (this.pageType == 3) {
        text = "立即续费";
      } else if (this.pageType == 4) {
        text = "立即升级";
      }
      return text;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      let g = [];
      if (this.pageType == 3) {
        //立即续费
        GET_RENEW().then(({ data }) => {
          this.gradeInfo = data;
          g = this.gradeInfo.goods_id;
          this.getGoodsInfo(g);
        });
      } else if (this.pageType == 4) {
        //立即升级
        GET_UPGRADE().then(({ data }) => {
          //判断是否是最高等级
          if (data.length == 0) {
            this.$Toast("您当前已是最高等级");
            this.$router.replace({ name: "microshop-gradecentre" });
            return false;
          }

          this.gradeInfo = data;
          g = this.gradeInfo[0].goods_id;
          this.getGoodsInfo(g);
        });
      } else {
        this.goods.forEach(e => {
          g.push(e.goods_id);
        });

        this.getGoodsInfo(g);
      }
    },
    async getGoodsInfo(g) {
      let goods_id = null;
      this.goodslist = [];
      const $this = this;
      for (let i = 0; i < g.length; i++) {
        goods_id = g[i];
        this.goodsParams.goods_id = goods_id;
        try {
          const data = await GET_GOODSDETAIL(this.goodsParams);
          this.goodslist.push(data.data.goods_detail);
          this.$Toast.clear();
        } catch (e) {
          console.log(e);
        }
      }
      this.goods_detail = this.goodslist[0] || {};
      this.goodsParams.goods_id = null;
      this.gradeFlag = true;
    },
    onGoods(goods_id, index) {
      if (this.goodsParams.goods_id === goods_id) {
        //防止重复点击等级商品重复提交请求接口
        return false;
      }
      this.goodsParams.goods_id = goods_id;
      this.goods_detail = this.goodslist[index];
    },
    onSubmit() {
      this.$refs.childgoodsinfo.onSku("buy");
    },
    getItem(index) {
      //切换等级
      const $this = this;
      if ($this.active === index) {
        //防止重复点击等级重复提交请求接口
        return false;
      }
      if (!$this.gradeFlag) return;
      $this.gradeFlag = false;
      $this.active = index;
      let g = $this.gradeInfo[index].goods_id;
      $this.getGoodsInfo(g);
    }
  },
  components: {
    PostHeader,
    PostGoodsGroup,
    PostGoodsInfo,
    PostBenefit,
    PostGoodsDescript
  }
};
</script>

<style scoped>
.content {
  padding-bottom: 70px;
}
.cell-group {
  margin: 10px 0;
  min-height: 144px;
}
.foot-btn {
  width: 100%;
  position: fixed;
  bottom: 15px;
  z-index: 100;
  padding: 0 30px;
}
.btn-flex-column {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -ms-flex-flow: column;
  flex-flow: column;
  line-height: 1.2;
}
.goods-name {
  display: flex;
  font-size: 12px;
  white-space: nowrap;
  justify-content: center;
}
.tag {
  color: #333;
  padding: 2px 5px;
  line-height: normal;
  border-radius: 3px;
  display: inline-block;
  font-size: 14px;
  border: 1px solid #999;
  margin-right: 6px;
}
.active {
  color: #fff;
  background-color: rgb(255, 68, 68);
  border: 1px solid rgb(255, 68, 68);
}
</style>
