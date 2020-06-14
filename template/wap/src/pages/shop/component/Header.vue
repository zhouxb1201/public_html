<template>
  <div>
    <div class="head">
      <img class="bg" :src="bgSrc" />
      <component :is="'Style0'+styleType" class="info" :info="item.info" />
      <HeadBtn
        class="btn-icon"
        icon="v-icon-menu1"
        dir="right"
        event
        @click="showRightMenu = !showRightMenu"
      />
      <transition name="van-fade">
        <div class="nav-bar-menu" v-show="showRightMenu">
          <router-link class="item" v-for="(item,index) in rightMenu" :key="index" :to="item.path">
            <van-icon :name="item.icon" />
            {{item.name}}
          </router-link>
        </div>
      </transition>
    </div>
    <van-cell value-class="search-cell">
      <van-button class="btn" round size="small" @click="onSearch('search')">
        <van-icon name="search" class="search-icon" />搜索
      </van-button>
      <van-button class="btn" round size="small" @click="onSearch">全部商品</van-button>
      <van-button
        class="btn"
        round
        size="small"
        v-if="showService"
        id="WS-SHOW-CHAT"
        @click="openKefu"
      >联系客服</van-button>
    </van-cell>
    <van-cell
      is-link
      title="门店列表"
      :to="{name:'store-list',query:{shop_id:$route.params.shopid}}"
      v-if="item.info.has_store"
    />
  </div>
</template>

<script>
import HeadBtn from "@/components/HeadBtn";
import { style } from "./header-style";
import { qlkefu } from "@/mixins";
export default {
  data() {
    return {
      rightMenu: [
        {
          icon: "wap-home",
          name: "首页",
          path: "/"
        },
        {
          icon: "apps-o",
          name: "分类",
          path: "/goods/category"
        },
        {
          icon: "cart",
          name: "购物车",
          path: "/mall/cart"
        },
        {
          icon: "contact",
          name: "会员中心",
          path: "/member/centre"
        }
      ],
      showRightMenu: false,
      showService: false
    };
  },
  props: {
    item: Object
  },
  mixins: [qlkefu],
  computed: {
    styleType() {
      let type = 1;
      if (this.item.params && this.item.params.styletype) {
        type = this.item.params.styletype;
      }
      return type;
    },
    bgSrc() {
      let src = "";
      if (this.item.style && this.item.style.backgroundimage) {
        src = this.item.style.backgroundimage;
      } else {
        src = this.$BASEIMGPATH + "style/shop-head-0" + this.styleType + ".jpg";
      }
      return src;
    }
  },
  mounted() {
    const $this = this;
    $this
      .getKefu($this.$route.params.shopid)
      .then(data => {
        if ($this.$store.getters.token) {
          $this.loadKefu(data.domain).then(() => {
            $this.showService = true;
            $this.$nextTick(() => {
              $this.serverFlag = true;
              const {
                uid,
                username,
                member_img,
                reg_time
              } = $this.$store.state.member.info;
              qlkefuChat.init({
                uid,
                uName: username,
                avatar: member_img,
                regTime: reg_time || "",
                goods: {
                  goods_id: "",
                  goods_name: "",
                  price: "",
                  pic_cover: ""
                }
              });
            });
          });
        } else {
          $this.showService = true;
        }
      })
      .catch(() => {});
  },
  methods: {
    onSearch(action) {
      if (action == "search") {
        this.$router.push({
          path: "/search",
          query: {
            type: "goods",
            shop_id: this.$route.params.shopid
          }
        });
      } else {
        this.$router.push({
          path: "/goods/list",
          query: {
            shop_id: this.$route.params.shopid
          }
        });
      }
    }
  },
  components: {
    HeadBtn,
    ...style
  }
};
</script>

<style scoped>
.head {
  position: relative;
}

.head .bg {
  display: block;
  width: 100%;
  min-height: 86px;
  background: #fff;
}

.info {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 10;
}

.head .btn-icon {
  position: absolute;
}

.search-cell .btn {
  margin-right: 5px;
  padding: 0 10px;
  color: #606266;
}

.search-cell .btn .search-icon {
  vertical-align: middle;
  margin-right: 2px;
  font-size: 14px;
  margin-top: -2px;
}

.nav-bar-menu {
  position: absolute;
  right: 15px;
  top: 60px;
}
</style>

