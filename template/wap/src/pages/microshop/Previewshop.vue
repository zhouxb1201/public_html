<template>
  <Layout ref="load" class="microshop-previewshop bg-f8">
    <!--head-->
    <div class="preview-head" :style="headBg">
      <div class="preview-info">
        <van-row>
          <van-col class="fl-none">
            <van-row type="flex">
              <van-col span="5">
                <div class="logo">
                  <img
                    :src="shopkeeperid ? microshop_logo : mic_logo"
                    :onerror="$ERRORPIC.noAvatar"
                  />
                </div>
              </van-col>
              <van-col span="18">
                <div class="name">
                  <div class="text">
                    {{ shopkeeperid ? microshop_name : mic_name }}
                  </div>
                  <div class="fans">
                    {{ shopkeeperid ? microshop_introduce : mic_introduce }}
                  </div>
                </div>
              </van-col>
            </van-row>
          </van-col>
        </van-row>
      </div>
    </div>
    <div class="preview-nav">
      <van-row type="flex" justify="center">
        <van-col span="12" @click.native="toGoodsList">全部商品</van-col>
        <van-col span="12" @click.native="toCategory">商品分类</van-col>
      </van-row>
    </div>
    <!--category-icon-->
    <div class="vui-icon-group bg-fff">
      <div
        class="vui-icon-col e-handle"
        v-for="(child, index) in items"
        :key="index"
      >
        <div v-for="(second, index) in child.second_category" :key="index">
          <div
            v-for="(third, index) in second.third_category"
            :key="index"
            @click="
              toList(
                third.category_id,
                third.short_name ? third.short_name : third.category_name
              )
            "
          >
            <div class="icon">
              <img
                v-lazy="third.category_pic"
                :key="third.category_pic"
                pic-type="square"
              />
            </div>
            <div class="text bg-fff">
              {{ third.short_name ? third.short_name : third.category_name }}
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--goodslist-->
    <Divider title="店铺精选" class="preview-divider">
      <div slot="html" class="ofh bg-fff">
        <GoodsTab
          v-for="(item, index) in goodslist"
          :key="index"
          :id="item.goods_id"
          :image="item.logo"
          :name="item.goods_name"
          :price="item.price"
          :marketPrice="item.market_price"
          :sales="item.sales"
          :isShow="false"
          :link="
            '/goods/detail/' + item.goods_id + '?shopkeeper_id=' + shopkeeper_id
          "
        />
      </div>
    </Divider>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import {
  GET_PREVIEWMICROSHOP,
  GET_PREVIEWMICROSHOGOODS
} from "@/api/microshop";
import Divider from "@/components/Divider";
import GoodsTab from "./component/GoodsTab";
import { GET_CENTREINFO } from "@/api/microshop";
export default sfc({
  name: "microshop-previewshop",
  data() {
    return {
      items: [],
      goodslist: [],
      shopkeeper_id: null,
      mic_logo: null,
      mic_name: null,
      mic_introduce: null
    };
  },
  computed: {
    set() {
      return this.$store.state.microshop.set;
    },
    microshop_logo() {
      const { set } = this;
      return set && set.microshop_logo ? set.microshop_logo : " ";
    },
    microshop_name() {
      const { set } = this;
      if (set && set.microshop_name) {
        document.title = set.microshop_name;
      }
      return set && set.microshop_name ? set.microshop_name : "";
    },
    microshop_introduce() {
      const { set } = this;
      return set && set.microshop_introduce ? set.microshop_introduce : "";
    },
    info() {
      return this.$store.state.microshop.info;
    },
    shopkeeperid() {
      const { info } = this;
      return info && info.uid ? info.uid : "";
    },
    headBg() {
      if (this.set) {
        return {
          backgroundImage: "url(" + this.set.shopRecruitment_logo + ")"
        };
      }
      return "";
    }
  },
  mounted() {
    if (this.$route.query.shopkeeper_id) {
      //判断是否是从分享链接进来的
      this.shopkeeper_id = this.$route.query.shopkeeper_id;
      let params = {
        shopkeeper_id: this.shopkeeper_id
      };
      GET_CENTREINFO(params)
        .then(res => {
          this.mic_logo = res.data.microshop_logo;
          this.mic_name = res.data.microshop_name;
          if (this.mic_name) {
            document.title = this.mic_name;
          }
          this.mic_introduce = res.data.microshop_introduce;
          this.loadData();
          this.$refs.load.success();
        })
        .catch(e => {
          this.$refs.load.fail();
        });
    } else {
      this.$store
        .dispatch("getMicroshopInfo")
        .then(res => {
          this.shopkeeper_id = this.shopkeeperid;
          this.loadData();
          this.$refs.load.success();
        })
        .catch(error => {
          this.$refs.load.fail();
        });
    }
  },
  methods: {
    loadData() {
      let params = {
        shopkeeper_id: this.shopkeeper_id
      };
      GET_PREVIEWMICROSHOP(params).then(res => {
        this.goodslist = res.data.goods_list;
      });
      GET_PREVIEWMICROSHOGOODS(params).then(res => {
        //获取商品分类
        this.items = res.data;
      });
    },
    toList(id, name) {
      this.$router.push({
        path: "/microshop/preview/list",
        query: {
          category_id: id,
          text: name,
          shopkeeper_id: this.shopkeeper_id
        }
      });
    },
    toGoodsList() {
      this.$router.push({
        path: "/microshop/preview/list",
        query: {
          shopkeeper_id: this.shopkeeper_id
        }
      });
    },
    toCategory() {
      this.$router.push({
        path: "/microshop/preview/category",
        query: {
          shopkeeper_id: this.shopkeeper_id
        }
      });
    }
  },
  components: {
    Divider,
    GoodsTab
  }
});
</script>

<style scoped>
.preview-divider >>> .divider-head::before {
  border-color: red !important;
}
.preview-divider >>> .divider-head {
  margin: 0 20% !important;
}
.preview-divider >>> .divider-head span {
  color: red !important;
}
.preview-head {
  background: -webkit-gradient(
    linear,
    left top,
    right top,
    from(#f87671),
    to(#fcba71)
  );
  background: linear-gradient(left, #f87671, #fcba71);
  background: -webkit-gradient(
    linear,
    left top,
    right top,
    from(#f87671),
    to(#fcba71)
  );
  background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}
.preview-info {
  padding: 10px 15px;
}

.preview-info .logo {
  padding-bottom: 100%;
  width: 100%;
  position: relative;
  background: #fafafa;
}

.preview-info .logo img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.preview-info .name {
  padding-left: 10px;
  color: #fff;
  line-height: 20px;
  letter-spacing: 2px;
}
.preview-info .name .fans {
  font-size: 12px;
}
.fl-none {
  float: none;
}
.preview-nav {
  background-color: #fff;
  padding: 12px;
  text-align: center;
  font-size: 14px;
  position: relative;
}
.preview-nav::before {
  content: "";
  width: 1px;
  height: 70%;
  left: 50%;
  top: 15%;
  background-color: #f8f8f8;
  transform: scaleX(0.5);
  position: absolute;
  z-index: 10;
  display: block;
}
.bg-fff {
  background-color: #fff;
}
.ofh {
  overflow: hidden;
}
/*******商品分类图标********/
.vui-icon-group {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: 10px;
  margin: 10px;
}
.vui-icon-group .vui-icon-col {
  width: 25%;
  height: auto;
  position: relative;
  padding: 0;
  margin: 10px 0;
  text-align: center;
  transition: background-color 300ms;
  -webkit-transition: background-color 300ms;
  float: left;
  border: none !important;
}
/*.vui-icon-group.col-3 .vui-icon-col {
  width: 33.333333%;
}
.vui-icon-group.col-4 .vui-icon-col {
  width: 25%;
}
.vui-icon-group.col-5 .vui-icon-col {
  width: 20%;
}*/

.vui-icon-group .vui-icon-col .icon {
  height: 48px;
  margin: auto;
  text-align: center;
  line-height: 48px;
  padding-top: 4px;
}
.vui-icon-group .vui-icon-col .icon img {
  height: 44px;
  width: 44px;
}
.vui-icon-group .vui-icon-col .text {
  font-size: 12px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 8px 4px 4px;
  color: #7a7a7a;
}
</style>
